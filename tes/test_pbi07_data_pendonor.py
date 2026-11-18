"""PBI-07 Data pendonor dan pencatatan donasi."""

import os
import re
import uuid
from datetime import date, timedelta
from urllib.parse import urlparse

import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait

BASE_URL = os.getenv("BASE_URL", "http://127.0.0.1:8000")
TIMEOUT = 10

# Kotak pesan pada layout aplikasi (flash sukses = hijau, kesalahan = merah)
KOTAK_ERROR = (By.CSS_SELECTOR, "div.bg-red-50.border-red-200")
KOTAK_SUKSES = (By.CSS_SELECTOR, "div.bg-green-50.border-green-200")

# Akun uji dari database/seeders/UserSeeder.php
AKUN = {
    "admin_pmi": "admin.pmi@bloodconnect.id",
    "petugas_rs": "petugas.rs@bloodconnect.id",
    "pendonor": "pendonor@bloodconnect.id",   # terhubung ke "Pendonor Contoh" (DemoSeeder)
}
PASSWORD_BENAR = "password123"


@pytest.fixture
def driver():
    options = webdriver.ChromeOptions()
    if os.getenv("HEADLESS") == "1":
        options.add_argument("--headless=new")
    d = webdriver.Chrome(options=options)
    d.maximize_window()
    yield d
    d.quit()


def buka(driver, url):
    driver.get(BASE_URL + url)


def path_sekarang(driver):
    return urlparse(driver.current_url).path


def uniq(awal=""):
    return f"{awal}{uuid.uuid4().hex[:8]}"


def hari(selisih=0):
    """Tanggal ISO hari ini ditambah selisih hari."""
    return (date.today() + timedelta(days=selisih)).isoformat()


def klik(driver, by, selector):
    el = WebDriverWait(driver, TIMEOUT).until(EC.presence_of_element_located((by, selector)))
    driver.execute_script("arguments[0].scrollIntoView({block:'center'});", el)
    WebDriverWait(driver, TIMEOUT).until(EC.element_to_be_clickable((by, selector)))
    el.click()


def isi(driver, name, nilai):
    """Isi field; nilai kosong ('' atau None) berarti field dikosongkan."""
    el = WebDriverWait(driver, TIMEOUT).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, f"[name='{name}']")))
    el.clear()
    if nilai is not None and str(nilai) != "":
        el.send_keys(str(nilai))


def isi_js(driver, name, nilai):
    """Untuk input date/time agar tidak bergantung pada locale browser."""
    el = driver.find_element(By.CSS_SELECTOR, f"[name='{name}']")
    driver.execute_script(
        "arguments[0].value = arguments[1];"
        "arguments[0].dispatchEvent(new Event('input', {bubbles:true}));"
        "arguments[0].dispatchEvent(new Event('change', {bubbles:true}));", el, str(nilai))


def pilih(driver, name, value):
    Select(driver.find_element(By.CSS_SELECTOR, f"select[name='{name}']")).select_by_value(value)


def radio(driver, name, value):
    """Radio golongan/rhesus/hasil uji disembunyikan (sr-only), jadi diklik lewat JS."""
    driver.execute_script(
        "document.querySelector(\"input[name='%s'][value='%s']\").click();" % (name, value))


def setuju_dialog(driver):
    WebDriverWait(driver, TIMEOUT).until(EC.alert_is_present()).accept()


def login(driver, role):
    """Masuk lewat halaman login dengan akun seeder sesuai peran."""
    buka(driver, "/login")
    klik(driver, By.CSS_SELECTOR, f"button[data-role='{role}']")
    isi(driver, "email", AKUN[role])
    isi(driver, "password", PASSWORD_BENAR)
    klik(driver, By.CSS_SELECTOR, "form button:not([type='button'])")
    WebDriverWait(driver, TIMEOUT).until(lambda d: path_sekarang(d) != "/login")


def cek_hasil(driver, expected_type, expected_value):
    """Pemeriksa hasil bersama, tiap jenis hasil sama seperti pada test_login."""
    if expected_type == "url":
        WebDriverWait(driver, TIMEOUT).until(EC.url_contains(expected_value))
        assert path_sekarang(driver) == expected_value

    elif expected_type == "keluar_form":
        # Berpindah dari halaman form = server menerima data
        WebDriverWait(driver, TIMEOUT).until(lambda d: path_sekarang(d) != expected_value)

    elif expected_type == "error":
        pesan = WebDriverWait(driver, TIMEOUT).until(
            EC.visibility_of_element_located(KOTAK_ERROR)
        ).text
        assert expected_value in pesan

    elif expected_type == "sukses":
        pesan = WebDriverWait(driver, TIMEOUT).until(
            EC.visibility_of_element_located(KOTAK_SUKSES)
        ).text
        assert expected_value in pesan

    elif expected_type == "html5_required":
        field = driver.find_element(By.CSS_SELECTOR, f"[name='{expected_value}']")
        assert driver.execute_script("return arguments[0].validity.valueMissing;", field)

    elif expected_type == "html5_invalid":
        # Batas min/max/type ditolak browser sehingga form tidak terkirim
        field = driver.find_element(By.CSS_SELECTOR, f"[name='{expected_value}']")
        assert not driver.execute_script("return arguments[0].validity.valid;", field)

    elif expected_type == "halaman_memuat":
        assert expected_value in driver.page_source

    elif expected_type == "403":
        assert "403" in driver.page_source or "Forbidden" in driver.page_source

    else:
        raise AssertionError(f"expected_type tidak dikenal: {expected_type}")


def isi_pendonor(driver, nama, umur=30, no_hp="081234567890", kota="Jakarta"):
    buka(driver, "/admin/pendonor/buat")
    isi(driver, "nama", nama)
    isi_js(driver, "tanggal_lahir", hari(-int(365.25 * umur)))
    pilih(driver, "jenis_kelamin", "L")
    pilih(driver, "golongan", "O")
    pilih(driver, "rhesus", "pos")
    isi(driver, "no_hp", no_hp)
    isi(driver, "kota", kota)
    klik(driver, By.XPATH, "//form//button[normalize-space()='Simpan']")


def buat_pendonor(driver, nama=None, umur=30):
    """Buat pendonor baru dan tunggu halaman detailnya terbuka."""
    nama = nama or "Uji " + uniq()
    isi_pendonor(driver, nama, umur)
    WebDriverWait(driver, TIMEOUT).until(lambda d: path_sekarang(d) != "/admin/pendonor/buat")
    return nama


PERIKSA_NORMAL = (("hemoglobin", 14.0), ("sistolik", 120), ("diastolik", 80),
                  ("berat_kg", 60), ("suhu_tubuh", 36.6), ("nadi", 72))


@pytest.mark.parametrize(
    "umur,no_hp,kota,expected_type,expected_value",
    [
        # TC-01  EP kelas valid: pendonor usia 30 dengan data lengkap
        (30, "081234567890", "Jakarta", "keluar_form", "/admin/pendonor/buat"),
        # TC-02  EP kelas invalid: usia 16.5 tahun (di bawah 17)
        (16.5, "081234567890", "Jakarta", "error", "Usia pendonor minimal 17 tahun."),
        # TC-03  EP kelas invalid: usia 66.5 tahun (di atas 65)
        (66.5, "081234567890", "Jakarta", "error", "Usia pendonor maksimal 65 tahun."),
        # TC-04  BVA no. HP 7 karakter: di bawah batas 8
        (30, "0812345", "Jakarta", "error", "Nomor telepon tidak valid."),
        # TC-05  BVA no. HP 8 karakter: batas bawah
        (30, "08123456", "Jakarta", "keluar_form", "/admin/pendonor/buat"),
        # TC-06  EP kelas invalid: no. HP berisi huruf
        (30, "abcdefghij", "Jakarta", "error", "Nomor telepon tidak valid."),
        # TC-07  EP kelas invalid: kota kosong (ditolak validasi HTML5)
        (30, "081234567890", "", "html5_required", "kota"),
    ],
    ids=["TC-01", "TC-02", "TC-03", "TC-04", "TC-05", "TC-06", "TC-07"],
)
def test_data_pendonor(driver, umur, no_hp, kota, expected_type, expected_value):
    login(driver, "admin_pmi")
    nama = "Uji " + uniq()
    isi_pendonor(driver, nama, umur, no_hp, kota)
    cek_hasil(driver, expected_type, expected_value)
    if expected_type == "keluar_form":
        assert nama in driver.page_source


def test_pendonor_duplikat_butuh_konfirmasi(driver):
    # Kelas peringatan: nama + tanggal lahir sama ditolak tanpa konfirmasi, diterima bila dicentang
    login(driver, "admin_pmi")
    nama = buat_pendonor(driver)
    isi_pendonor(driver, nama)
    WebDriverWait(driver, TIMEOUT).until(lambda d: "Sudah ada pendonor" in d.page_source)
    driver.execute_script("document.querySelector(\"input[name='konfirmasi_duplikat']\").click();")
    klik(driver, By.XPATH, "//form//button[normalize-space()='Simpan']")
    cek_hasil(driver, "sukses", "berhasil ditambahkan")


@pytest.mark.parametrize(
    "pemeriksaan,volume,expected_type,expected_value",
    [
        # TC-01  EP kelas valid: donasi tanpa pemeriksaan (pemeriksaan tidak diwajibkan)
        ("tanpa", 350, "sukses", "Donasi dicatat"),
        # TC-02  EP kelas valid: donasi setelah pemeriksaan layak
        ("layak", 350, "sukses", "Donasi dicatat"),
        # TC-03  EP kelas invalid: pemeriksaan hari ini TIDAK LAYAK (Hb 9.0) menghalangi donasi
        ("tidak_layak", 350, "error", "TIDAK LAYAK"),
        # TC-04  BVA volume donasi 99 ml: di bawah batas 100
        ("layak", 99, "html5_invalid", "volume_ml"),
        # TC-05  BVA volume donasi 100 ml: batas bawah
        ("layak", 100, "sukses", "Donasi dicatat"),
        # TC-06  BVA volume donasi 500 ml: batas atas
        ("layak", 500, "sukses", "Donasi dicatat"),
        # TC-07  BVA volume donasi 501 ml: di atas batas atas
        ("layak", 501, "html5_invalid", "volume_ml"),
        # TC-08  Aturan bisnis: donasi kedua pada tanggal sama melanggar jeda 60 hari
        ("dua_kali", 350, "error", "Jarak antar donasi minimal 60 hari"),
    ],
    ids=["TC-01", "TC-02", "TC-03", "TC-04", "TC-05", "TC-06", "TC-07", "TC-08"],
)
def test_donasi(driver, pemeriksaan, volume, expected_type, expected_value):
    login(driver, "admin_pmi")
    buat_pendonor(driver)
    if pemeriksaan in ("layak", "tidak_layak"):
        for name, nilai in PERIKSA_NORMAL:
            isi(driver, name, 9.0 if (pemeriksaan == "tidak_layak" and name == "hemoglobin") else nilai)
        klik(driver, By.XPATH, "//button[contains(.,'Periksa')]")
        WebDriverWait(driver, TIMEOUT).until(EC.visibility_of_element_located(
            ("xpath", "(//span[contains(@class,'rounded-full')]"
                      "[normalize-space()='Layak' or normalize-space()='Tidak layak'])[1]")))
    isi_js(driver, "tanggal_donor", hari(0))
    isi(driver, "volume_ml", volume)
    klik(driver, By.XPATH, "//button[contains(.,'Simpan Donasi')]")
    if pemeriksaan == "dua_kali":
        cek_hasil(driver, "sukses", "Donasi dicatat")
        isi_js(driver, "tanggal_donor", hari(0))
        isi(driver, "volume_ml", volume)
        klik(driver, By.XPATH, "//button[contains(.,'Simpan Donasi')]")
    cek_hasil(driver, expected_type, expected_value)
