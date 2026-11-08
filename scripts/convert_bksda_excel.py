import csv
import re
from collections import defaultdict
from pathlib import Path

from openpyxl import load_workbook

ROOT = Path(r"D:\xampp\htdocs\bksda_pemberdayaan")
DESA_FILE = Path(r"d:\Magang BKSDA\Konversi\Desa_KK.xlsx")
KELOMPOK_FILE = Path(r"d:\Magang BKSDA\Konversi\Matriks Kelompok Binaan 2020-2026.xlsx")
TEMPLATE_DIR = ROOT / "storage" / "app" / "templates"

NAME_FIXES = {
    "SM. Bangkiriang": "SM Bakiriang",
    "SM Bangkiriang": "SM Bakiriang",
    "SM. Tj. Santigi": "SM Tanjung Santigi",
    "SM Tj. Santigi": "SM Tanjung Santigi",
    "SM. Pinjan Tj. Matop": "SM Pinjang Tanjung Matop",
    "SM Pinjan Tj. Matop": "SM Pinjang Tanjung Matop",
    "SM Pinjan": "SM Pinjang Tanjung Matop",
    "TB Landusa Tomata": "TB Londusa Tomata",
    "TB Londusa Tomata": "TB Londusa Tomata",
    "CA. Pati-pati": "CA Pati-pati",
    "CA Pati Pati": "CA Pati-pati",
    "CA. Pamona": "CA Pamona",
    "CA. Morowali": "CA Morowali",
    "CA. Tanjung Api": "CA Tanjung Api",
    "CA. Gunung Dako": "CA Gunung Dako",
    "CA. Gunung Sojol": "CA Gunung Sojol",
    "CA. Gunung Tinombala": "CA Gunung Tinombala",
    "CA. Pangi Binangga": "CA Pangi Binangga",
    "TWA Pulau Tokobae": "TWA Tokobae",
    "TWA Tokobae": "TWA Tokobae",
    "SM. Pulau Pasoso": "SM Pulau Pasoso",
    "SM. Pulau Dolangan": "SM Pulau Dolangan",
    "SM. Lombuyan": "SM Lombuyan",
}


def normalize_kawasan_name(value):
    if value is None:
        return ""

    s = str(value).strip()
    s = s.replace("CA.", "CA ")
    s = s.replace("SM.", "SM ")
    s = s.replace("TWA.", "TWA ")
    s = s.replace("TB.", "TB ")
    s = s.replace("Tj.", "Tanjung")
    s = s.replace("Gn.", "Gunung")
    s = s.replace("Bangkiriang", "Bakiriang")
    s = s.replace("Landusa", "Londusa")
    s = s.replace("Pinjan Tanjung", "Pinjang Tanjung")
    s = s.replace("Pati Pati", "Pati-pati")
    s = s.replace("Pati-Pati", "Pati-pati")
    s = re.sub(r"\s+", " ", s)
    s = s.replace("  ", " ").strip()
    s = NAME_FIXES.get(s, s)
    return s


def normalize_kawasan_function(value):
    s = normalize_kawasan_name(value)
    if s.startswith("CA "):
        return "CA"
    if s.startswith("SM "):
        return "SM"
    if s.startswith("TWA "):
        return "TWA"
    if s.startswith("TB "):
        return "TB"
    return s.split()[0] if s else ""


def normalize_phone(value):
    if value is None:
        return ""
    s = str(value).strip()
    if s.lower() in {"none", "nan", "-", ""}:
        return ""
    s = s.replace(" ", "")
    s = s.replace("-", "")
    return s


def normalize_desa(value):
    if value is None:
        return ""
    s = str(value).strip().replace("  ", " ")
    return s


def normalize_jenis_usaha(value):
    if value is None:
        return ""
    s = str(value).strip()
    return s if s else ""


def normalize_group_name(value):
    if value is None:
        return ""
    s = str(value).strip()
    s = re.sub(r"\s+", " ", s)
    return s


def build_kawasan_csv():
    wb = load_workbook(DESA_FILE, data_only=True)
    ws = wb["Desa_KK"]
    rows = list(ws.iter_rows(values_only=True))
    header = rows[0]
    idx = {name: i for i, name in enumerate(header)}
    counts = defaultdict(set)

    for row in rows[1:]:
        if not row or not row[idx["N_Kawasan"]]:
            continue
        nama = normalize_kawasan_name(row[idx["N_Kawasan"]])
        desa = normalize_desa(row[idx["DESA"]])
        if nama and desa:
            counts[nama].add(desa)

    output = TEMPLATE_DIR / "kawasan_template.csv"
    with output.open("w", newline="", encoding="utf-8") as fh:
        writer = csv.writer(fh)
        writer.writerow(["nama_kawasan", "fungsi_kawasan", "jumlah_desa_sekitar", "jumlah_desa_difasilitasi"])
        for nama in sorted(counts, key=lambda x: x.lower()):
            desa_count = len(counts[nama])
            writer.writerow([nama, normalize_kawasan_function(nama), desa_count, desa_count])

        # TWA Tokobae is present in the NTE dataset but not in the Desa_KK spatial worksheet,
        # so it is added explicitly to keep the kawasan reference set complete.
        writer.writerow(["TWA Tokobae", "TWA", 2, 2])

    print(f"Generated kawasan file: {output} rows={sum(1 for _ in output.open('r', encoding='utf-8')) - 1}")


def build_kelompok_csv():
    wb = load_workbook(KELOMPOK_FILE, data_only=True)
    ws = wb["Table 1 (2)"]
    rows = list(ws.iter_rows(values_only=True))

    header_row = None
    for idx, row in enumerate(rows):
        if row and any(str(v).strip() == "Nama Kelompok" for v in row if v is not None):
            header_row = idx
            break

    if header_row is None:
        raise RuntimeError("Header 'Nama Kelompok' not found in workbook")

    header = rows[header_row]
    mapping = {str(v).strip(): i for i, v in enumerate(header) if v is not None}
    last_year = None
    records = []

    for row in rows[header_row + 1:]:
        if row is None or not any(v is not None and str(v).strip() not in ["", "None"] for v in row[:10]):
            continue

        tahun = row[mapping.get("Tahun", 1)] if "Tahun" in mapping else None
        if tahun is not None and str(tahun).strip() not in ["", "None"]:
            last_year = int(float(tahun))

        nama_kawasan = normalize_kawasan_name(row[mapping.get("Kawasan", 2)] if "Kawasan" in mapping else None)
        nama_desa = normalize_desa(row[mapping.get("Desa", 3)] if "Desa" in mapping else None)
        kecamatan = normalize_desa(row[mapping.get("Kecamatan", 4)] if "Kecamatan" in mapping else None)
        kabupaten = normalize_desa(row[mapping.get("Kabupaten", 5)] if "Kabupaten" in mapping else None)
        nama_kelompok = normalize_group_name(row[mapping.get("Nama Kelompok", 6)] if "Nama Kelompok" in mapping else None)
        nama_ketua = row[mapping.get("Nama Ketua", 7)] if "Nama Ketua" in mapping else None
        nama_ketua = str(nama_ketua).strip() if nama_ketua not in (None, "") else ""
        no_hp = normalize_phone(row[mapping.get("No. Hp", 8)] if "No. Hp" in mapping else None)
        jenis_usaha = normalize_jenis_usaha(row[mapping.get("Jenis Usaha   Produk", 9)] if "Jenis Usaha   Produk" in mapping else None)
        sk_kelompok = row[mapping.get("SK Kelompok", 10)] if "SK Kelompok" in mapping else None
        sk_kelompok = "" if sk_kelompok is None else str(sk_kelompok).strip()
        anggota = row[mapping.get("Anggota (org)", 11)] if "Anggota (org)" in mapping else None
        jumlah_bantuan = row[mapping.get("Jumlah Bantuan", 12)] if "Jumlah Bantuan" in mapping else None

        if not nama_kawasan or not nama_kelompok:
            continue

        try:
            anggota_int = int(float(str(anggota).replace("(P)", "").strip())) if anggota not in (None, "") else 0
        except Exception:
            anggota_int = 0

        try:
            bantuan_num = float(str(jumlah_bantuan).replace(".", "").replace(",", ".")) if jumlah_bantuan not in (None, "") else 0
        except Exception:
            bantuan_num = 0

        records.append({
            "tahun_pembinaan": last_year or 2020,
            "nama_kawasan": nama_kawasan,
            "nama_desa": nama_desa,
            "kecamatan": kecamatan,
            "kabupaten": kabupaten,
            "nama_kelompok": nama_kelompok,
            "nama_ketua": nama_ketua,
            "no_hp": no_hp,
            "jenis_usaha": jenis_usaha,
            "sk_kelompok": sk_kelompok,
            "jumlah_anggota": anggota_int,
            "jumlah_bantuan": int(bantuan_num) if float(bantuan_num).is_integer() else bantuan_num,
        })

    output = TEMPLATE_DIR / "kelompok_template.csv"
    with output.open("w", newline="", encoding="utf-8") as fh:
        writer = csv.writer(fh)
        writer.writerow(["tahun_pembinaan", "nama_kawasan", "nama_desa", "kecamatan", "kabupaten", "nama_kelompok", "nama_ketua", "no_hp", "jenis_usaha", "sk_kelompok", "jumlah_anggota", "jumlah_bantuan"])
        for record in records:
            writer.writerow([
                record["tahun_pembinaan"],
                record["nama_kawasan"],
                record["nama_desa"],
                record["kecamatan"],
                record["kabupaten"],
                record["nama_kelompok"],
                record["nama_ketua"],
                record["no_hp"],
                record["jenis_usaha"],
                record["sk_kelompok"],
                record["jumlah_anggota"],
                record["jumlah_bantuan"],
            ])

    print(f"Generated kelompok file: {output} rows={len(records)}")


if __name__ == "__main__":
    TEMPLATE_DIR.mkdir(parents=True, exist_ok=True)
    build_kawasan_csv()
    build_kelompok_csv()
