import uuid
from datetime import date, timedelta

import pytest
from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait

BASE = "http://127.0.0.1:8000"
PETUGAS_EMAIL = "petugas.rs@bloodconnect.id"
PETUGAS_PASSWORD = "password123"


# ---------- Persiapan ----------
@pytest.fixture
def driver():
    d = webdriver.Chrome()
    d.maximize_window()
    yield d
    d.quit()


def tunggu(driver, kondisi, detik=10):
    return WebDriverWait(driver, detik).until(kondisi)


@pytest.fixture
def petugas(driver):
    """Login sebagai Petugas RS (memilih tab peran petugas_rs di halaman login)."""
    driver.get(f"{BASE}/login")
    tunggu(driver, EC.element_to_be_clickable((By.CSS_SELECTOR, "button[data-role='petugas_rs']"))).click()
    tunggu(driver, EC.visibility_of_element_located((By.NAME, "email"))).send_keys(PETUGAS_EMAIL)
    kolom_sandi = driver.find_element(By.NAME, "password")
    kolom_sandi.send_keys(PETUGAS_PASSWORD)
    kolom_sandi.send_keys(Keys.ENTER)
    tunggu(driver, EC.url_contains("/dashboard"))
    return driver


# ---------- Fungsi bantu ----------
def kode_baru():
    return "SEL-" + uuid.uuid4().hex[:6].upper()


def isi_form(driver, kode, volume):
    kemarin = (date.today() - timedelta(days=1)).isoformat()
    Select(tunggu(driver, EC.presence_of_element_located((By.NAME, "user_id")))).select_by_index(1)

    kolom_kode = driver.find_element(By.NAME, "blood_bag_code")
    kolom_kode.clear()
    kolom_kode.send_keys(kode)

    tanggal = driver.find_element(By.NAME, "donation_date")
    driver.execute_script("arguments[0].value = arguments[1];", tanggal, kemarin)

    kolom_volume = driver.find_element(By.NAME, "volume_ml")
    kolom_volume.clear()
    kolom_volume.send_keys(str(volume))


def klik_simpan(driver):
    driver.find_element(By.CSS_SELECTOR, "form button[type='submit']").click()


def catat_donasi(driver, kode, volume=350):
    driver.get(f"{BASE}/petugas/donasi/create")
    isi_form(driver, kode, volume)
    klik_simpan(driver)
    tunggu(driver, EC.visibility_of_element_located((By.CSS_SELECTOR, ".alert.ok")))


def cari_baris(driver, kode):
    driver.get(f"{BASE}/petugas/donasi?q={kode}")
    return tunggu(driver, EC.presence_of_element_located(
        (By.XPATH, f"//tr[td[contains(., '{kode}')]]")
    ))


def pesan_sukses(driver):
    return tunggu(driver, EC.visibility_of_element_located((By.CSS_SELECTOR, ".alert.ok"))).text


# ---------- Kasus uji ----------
def test_tc01_create_valid(petugas):
    kode = kode_baru()
    petugas.get(f"{BASE}/petugas/donasi/create")
    isi_form(petugas, kode, 350)
    klik_simpan(petugas)

    assert "Donasi berhasil dicatat" in pesan_sukses(petugas)
    baris = cari_baris(petugas, kode)
    assert "350 ml" in baris.text
    assert "Tercatat" in baris.text


def test_tc02_create_volume_di_atas_batas(petugas):
    kode = kode_baru()
    petugas.get(f"{BASE}/petugas/donasi/create")
    isi_form(petugas, kode, 501)

    volume = petugas.find_element(By.NAME, "volume_ml")
    assert petugas.execute_script("return arguments[0].validity.rangeOverflow;", volume)

    klik_simpan(petugas)
    petugas.get(f"{BASE}/petugas/donasi?q={kode}")
    assert "Belum ada donasi tercatat" in petugas.find_element(By.TAG_NAME, "body").text


def test_tc03_read_pencarian(petugas):
    kode = kode_baru()
    catat_donasi(petugas, kode, 350)

    baris = cari_baris(petugas, kode)
    assert kode in baris.text
    assert "350 ml" in baris.text

    petugas.get(f"{BASE}/petugas/donasi?q=TIDAK-ADA-{kode}")
    assert "Belum ada donasi tercatat" in petugas.find_element(By.TAG_NAME, "body").text


def test_tc04_update_volume(petugas):
    kode = kode_baru()
    catat_donasi(petugas, kode, 350)

    cari_baris(petugas, kode).find_element(By.LINK_TEXT, "Ubah").click()
    volume = tunggu(petugas, EC.visibility_of_element_located((By.NAME, "volume_ml")))
    volume.clear()
    volume.send_keys("450")
    klik_simpan(petugas)

    assert "Data donasi berhasil dikoreksi" in pesan_sukses(petugas)
    assert "450 ml" in cari_baris(petugas, kode).text


def test_tc05_batalkan_donasi(petugas):
    kode = kode_baru()
    catat_donasi(petugas, kode, 350)

    baris = cari_baris(petugas, kode)
    baris.find_element(By.NAME, "cancel_reason").send_keys("Salah input")
    baris.find_element(By.XPATH, ".//button[contains(., 'Batalkan')]").click()
    tunggu(petugas, EC.alert_is_present()).accept()

    assert "Donasi berhasil dibatalkan" in pesan_sukses(petugas)
    baris = cari_baris(petugas, kode)
    assert "Dibatalkan" in baris.text
    assert "Salah input" in baris.text
    assert len(baris.find_elements(By.LINK_TEXT, "Ubah")) == 0