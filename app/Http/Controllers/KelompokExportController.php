<?php

namespace App\Http\Controllers;

use App\Models\Kelompok;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use XMLWriter;
use ZipArchive;

class KelompokExportController extends Controller
{
    private const COLUMNS = [
        'No' => 'no',
        'Nama Kawasan' => 'kawasan.nama_kawasan',
        'Nama Desa' => 'desa.nama_desa',
        'Kecamatan' => 'kecamatan',
        'Kabupaten' => 'kabupaten',
        'Tahun Pembinaan' => 'tahun_pembinaan',
        'Tahun Pembentukan' => 'tahun_pembentukan',
        'Tahun Monitoring' => 'tahun_monitoring',
        'Nama Kelompok' => 'nama_kelompok',
        'Status Kelompok' => 'status_kelompok',
        'Nama Ketua' => 'nama_ketua',
        'Nomor HP / Kontak' => 'no_hp',
        'Jenis Usaha' => 'jenis_usaha',
        'Produk Unggulan' => 'produk_unggulan',
        'SK Kelompok' => 'sk_kelompok',
        'Jumlah Anggota Laki-laki' => 'jumlah_anggota_laki_laki',
        'Jumlah Anggota Perempuan' => 'jumlah_anggota_perempuan',
        'Total Anggota' => 'jumlah_anggota',
        'Jumlah Bantuan (Rp)' => 'jumlah_bantuan',
        'Latitude' => 'latitude',
        'Longitude' => 'longitude',
        'Catatan Kebutuhan / Hasil Monev' => 'catatan_kebutuhan_hasil_monev',
        'Dibuat Pada' => 'created_at',
        'Diperbarui Pada' => 'updated_at',
    ];

    /**
     * @var list<string>
     */
    private const NUMERIC_COLUMNS = [
        'no',
        'tahun_pembinaan',
        'tahun_pembentukan',
        'tahun_monitoring',
        'jumlah_anggota_laki_laki',
        'jumlah_anggota_perempuan',
        'jumlah_anggota',
        'jumlah_bantuan',
        'latitude',
        'longitude',
    ];

    /**
     * @var list<string>
     */
    private const SEARCHABLE_COLUMNS = [
        'kelompoks.nama_kelompok',
        'kelompoks.jenis_usaha',
    ];

    public function export(Request $request, string $format): BinaryFileResponse|StreamedResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);
        $groups = $this->groupsQuery(trim($validated['search'] ?? ''));

        return match ($format) {
            'csv' => $this->csvResponse($groups),
            'xlsx' => $this->xlsxResponse($groups),
            default => abort(404),
        };
    }

    private function groupsQuery(string $search): Builder
    {
        return Kelompok::query()
            ->with(['kawasan', 'desa'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $pattern = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
                $query->where(function (Builder $query) use ($pattern): void {
                    foreach (self::SEARCHABLE_COLUMNS as $column) {
                        $query->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$pattern]);
                    }

                    $query->orWhereHas('desa', fn (Builder $relation): Builder => $relation
                        ->whereRaw("nama_desa LIKE ? ESCAPE '!'", [$pattern]));
                });
            });
    }

    private function csvResponse(Builder $groups): StreamedResponse
    {
        return response()->streamDownload(function () use ($groups): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new RuntimeException('Tidak dapat membuka output untuk ekspor CSV.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            $this->writeCsvRow($output, array_keys(self::COLUMNS));
            $rowNumber = 0;

            $groups->chunkById(500, function (EloquentCollection $batch) use ($output, &$rowNumber): void {
                foreach ($batch as $group) {
                    $values = $this->valuesFor($group, ++$rowNumber);
                    $attributes = array_values(self::COLUMNS);
                    $cells = [];

                    foreach ($attributes as $index => $attribute) {
                        $value = $this->csvValue($attribute, $values[$index]);
                        if (! in_array($attribute, self::NUMERIC_COLUMNS, true)
                            && preg_match('/^[\t\r\n ]*[=+\-@]/u', $value) === 1) {
                            $value = "'".$value;
                        }
                        $cells[] = $value;
                    }

                    $this->writeCsvRow($output, $cells);
                }
            });

            fclose($output);
        }, 'kelompok-binaan.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  resource  $output
     * @param  list<string>  $cells
     */
    private function writeCsvRow($output, array $cells): void
    {
        if (fputcsv($output, $cells, ',', '"', '', "\r\n") === false) {
            throw new RuntimeException('Gagal menulis baris pada ekspor CSV.');
        }
    }

    private function xlsxResponse(Builder $groups): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'kelompok-binaan-');
        if ($path === false) {
            throw new RuntimeException('Tidak dapat membuat berkas sementara untuk ekspor Excel.');
        }

        $archive = new ZipArchive;
        $isOpen = false;

        try {
            if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Tidak dapat membuat berkas Excel.');
            }
            $isOpen = true;

            $files = [
                '[Content_Types].xml' => $this->contentTypesXml(),
                '_rels/.rels' => $this->packageRelationshipsXml(),
                'xl/workbook.xml' => $this->workbookXml(),
                'xl/_rels/workbook.xml.rels' => $this->workbookRelationshipsXml(),
                'xl/styles.xml' => $this->stylesXml(),
                'xl/worksheets/sheet1.xml' => $this->worksheetXml($groups),
            ];

            foreach ($files as $name => $contents) {
                if (! $archive->addFromString($name, $contents)) {
                    throw new RuntimeException("Gagal menulis bagian {$name} pada berkas Excel.");
                }
            }

            if (! $archive->close()) {
                $isOpen = false;
                throw new RuntimeException('Gagal menyelesaikan berkas Excel.');
            }
            $isOpen = false;
        } catch (Throwable $exception) {
            if ($isOpen) {
                $archive->close();
            }
            unlink($path);

            throw $exception;
        }

        return response()->download($path, 'kelompok-binaan.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function worksheetXml(Builder $groups): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $xml->startElement('sheetViews');
        $xml->startElement('sheetView');
        $xml->writeAttribute('workbookViewId', '0');
        $xml->startElement('pane');
        $xml->writeAttribute('ySplit', '1');
        $xml->writeAttribute('topLeftCell', 'A2');
        $xml->writeAttribute('activePane', 'bottomLeft');
        $xml->writeAttribute('state', 'frozen');
        $xml->endElement();
        $xml->startElement('selection');
        $xml->writeAttribute('pane', 'bottomLeft');
        $xml->writeAttribute('activeCell', 'A2');
        $xml->writeAttribute('sqref', 'A2');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();

        $xml->startElement('sheetFormatPr');
        $xml->writeAttribute('defaultRowHeight', '20');
        $xml->endElement();

        $xml->startElement('cols');
        foreach (array_keys(self::COLUMNS) as $index => $header) {
            $width = min(42, max(14, mb_strlen($header) + 4));
            $xml->startElement('col');
            $xml->writeAttribute('min', (string) ($index + 1));
            $xml->writeAttribute('max', (string) ($index + 1));
            $xml->writeAttribute('width', (string) $width);
            $xml->writeAttribute('customWidth', '1');
            $xml->endElement();
        }
        $xml->endElement();

        $xml->startElement('sheetData');
        $rowNumber = 1;
        $groupNumber = 0;
        $this->writeWorksheetRow($xml, $rowNumber, array_keys(self::COLUMNS), header: true);

        $groups->chunkById(500, function (EloquentCollection $batch) use ($xml, &$rowNumber, &$groupNumber): void {
            foreach ($batch as $group) {
                $rowNumber++;
                $groupNumber++;
                $this->writeWorksheetRow($xml, $rowNumber, $this->valuesFor($group, $groupNumber));
            }
        });

        $xml->endElement();
        $xml->startElement('autoFilter');
        $xml->writeAttribute('ref', 'A1:'.$this->columnLetter(count(self::COLUMNS)).$rowNumber);
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * @param  list<mixed>  $values
     */
    private function writeWorksheetRow(XMLWriter $xml, int $rowNumber, array $values, bool $header = false): void
    {
        $xml->startElement('row');
        $xml->writeAttribute('r', (string) $rowNumber);
        if ($header) {
            $xml->writeAttribute('ht', '30');
            $xml->writeAttribute('customHeight', '1');
        }

        $attributes = array_values(self::COLUMNS);
        foreach ($values as $index => $value) {
            $attribute = $header ? null : $attributes[$index];
            $xml->startElement('c');
            $xml->writeAttribute('r', $this->columnLetter($index + 1).$rowNumber);
            $xml->writeAttribute('s', $header ? '1' : $this->styleFor($attribute));

            if ($header) {
                $xml->writeAttribute('t', 'inlineStr');
                $xml->startElement('is');
                $xml->startElement('t');
                $xml->writeAttributeNs('xml', 'space', 'http://www.w3.org/XML/1998/namespace', 'preserve');
                $xml->text((string) $value);
                $xml->endElement();
                $xml->endElement();
            } elseif ($value !== null && $value !== '' && in_array($attribute, self::NUMERIC_COLUMNS, true) && is_numeric($value)) {
                $xml->startElement('v');
                $xml->text((string) $value);
                $xml->endElement();
            } elseif ($value !== null && $value !== '') {
                $xml->writeAttribute('t', 'inlineStr');
                $xml->startElement('is');
                $xml->startElement('t');
                $xml->writeAttributeNs('xml', 'space', 'http://www.w3.org/XML/1998/namespace', 'preserve');
                $xml->text($this->stringValue($value));
                $xml->endElement();
                $xml->endElement();
            }

            $xml->endElement();
        }

        $xml->endElement();
    }

    private function styleFor(?string $attribute): string
    {
        return match ($attribute) {
            'jumlah_bantuan' => '3',
            'latitude', 'longitude' => '4',
            default => in_array($attribute, self::NUMERIC_COLUMNS, true) ? '2' : '5',
        };
    }

    /**
     * @return list<mixed>
     */
    private function valuesFor(Kelompok $group, int $rowNumber): array
    {
        return array_map(
            fn (string $attribute): mixed => $attribute === 'no' ? $rowNumber : data_get($group, $attribute),
            array_values(self::COLUMNS),
        );
    }

    private function stringValue(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value === null ? '' : (string) $value;
    }

    private function csvValue(string $attribute, mixed $value): string
    {
        $precision = match ($attribute) {
            'jumlah_bantuan' => 2,
            'latitude', 'longitude' => 7,
            default => null,
        };

        if ($precision === null || $value === null || ! is_numeric($value)) {
            return $this->stringValue($value);
        }

        $number = (string) $value;
        $negative = str_starts_with($number, '-');
        $number = ltrim($number, '+-');
        [$whole, $fraction] = array_pad(explode('.', $number, 2), 2, '');
        $fraction = substr(str_pad($fraction, $precision, '0'), 0, $precision);

        return ($negative ? '-' : '').$whole.'.'.$fraction;
    }

    private function columnLetter(int $column): string
    {
        $letter = '';
        while ($column > 0) {
            $remainder = ($column - 1) % 26;
            $letter = chr(65 + $remainder).$letter;
            $column = intdiv($column - 1, 26);
        }

        return $letter;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function packageRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView/></bookViews>'
            .'<sheets><sheet name="Kelompok Binaan" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="2"><numFmt numFmtId="164" formatCode="#,##0.00"/><numFmt numFmtId="165" formatCode="0.0000000"/></numFmts>'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0F766E"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDCE5E3"/></left><right style="thin"><color rgb="FFDCE5E3"/></right><top style="thin"><color rgb="FFDCE5E3"/></top><bottom style="thin"><color rgb="FFDCE5E3"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="6">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
            .'<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
