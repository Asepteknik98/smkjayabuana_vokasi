# Tim Latihan Koding LKS — SMK Jaya Buana

> Belajar bersama, berlatih dengan disiplin, dan berkarya untuk prestasi.

Repository ini menjadi ruang latihan dan kolaborasi siswa/i **SMK Jaya Buana** dalam mempersiapkan diri menghadapi **Lomba Kompetensi Siswa (LKS) tingkat kabupaten**, dengan fokus pada keterampilan pemrograman dan pengembangan aplikasi web.

## Tujuan

- Meningkatkan kemampuan menyusun algoritma dan menyelesaikan masalah.
- Melatih pembuatan aplikasi yang berfungsi, rapi, dan mudah digunakan.
- Membiasakan penggunaan Git dan GitHub dalam kerja tim.
- Mengembangkan ketelitian, kemandirian, kedisiplinan, dan manajemen waktu.
- Membangun kesiapan teknis serta mental bertanding.

## Fokus Latihan

| Bidang | Kegiatan latihan |
|---|---|
| Algoritma | Percabangan, perulangan, fungsi, dan pemecahan masalah |
| Frontend | HTML, CSS, JavaScript, dan tampilan responsif |
| Backend | PHP, pemrosesan formulir, validasi, dan pengelolaan sesi |
| Database | MySQL/MariaDB, relasi tabel, query, dan CRUD |
| Kualitas aplikasi | Pengujian fitur, debugging, dan keamanan dasar |
| Kolaborasi | Branch, commit, push, review, dan merge |
| Simulasi lomba | Penyelesaian studi kasus dengan batas waktu |

Materi dan teknologi latihan disesuaikan oleh pembimbing berdasarkan kisi-kisi serta ketentuan resmi bidang lomba yang diikuti. Daftar di atas merupakan fokus latihan tim, bukan kisi-kisi resmi LKS.

## Pola Latihan

1. **Pahami tugas:** identifikasi kebutuhan, masukan, keluaran, dan batas waktu.
2. **Rancang solusi:** buat alur kerja, struktur halaman, dan rancangan database jika diperlukan.
3. **Kerjakan:** tulis kode secara bertahap dan gunakan penamaan yang jelas.
4. **Uji:** periksa kondisi normal, input tidak valid, dan kemungkinan kesalahan.
5. **Kumpulkan:** commit dan push ke branch masing-masing.
6. **Evaluasi:** jelaskan solusi, terima masukan, lalu perbaiki hasil latihan.

## Aturan Tim

- Kerjakan latihan dengan jujur dan pahami kode yang dikumpulkan.
- Gunakan branch sendiri dengan nama yang mudah dikenali.
- Simpan tugas pada folder yang ditentukan pembimbing.
- Jangan mengubah atau menghapus pekerjaan anggota lain tanpa koordinasi.
- Tulis pesan commit yang menjelaskan perubahan.
- Jangan mengunggah password, token, file `.env`, atau data pribadi siswa.
- Gunakan data contoh untuk latihan aplikasi dan database.
- Gabungkan perubahan ke `main` setelah diperiksa pembimbing atau pengelola repository.
- Saling membantu melalui penjelasan dan diskusi agar setiap anggota mampu bekerja mandiri.

## Panduan Git Bash

### Persiapan pertama

Pastikan undangan kolaborasi sudah diterima. Clone repository, kemudian masuk ke foldernya:

```bash
git clone https://github.com/Asepteknik98/smkjayabuana_vokasi.git
cd smkjayabuana_vokasi
```

Atur identitas untuk repository ini menggunakan nama sendiri dan email yang terhubung ke akun GitHub:

```bash
git config user.name "Nama Anda"
git config user.email "email-anda@example.com"
```

### Memulai tugas baru

Mulai dari `main` yang sudah diperbarui. Pastikan pekerjaan sebelumnya sudah disimpan melalui commit sebelum berpindah branch:

```bash
git switch main
git pull --ff-only origin main
git switch -c latihan-nama-topik
```

Contoh nama branch: `latihan-hilmi-crud`. Buat branch baru dari hasil clone untuk menjaga riwayat pekerjaan tetap terhubung dengan `main`.

### Mengirim hasil latihan

Setelah menyimpan file dan menguji program:

```bash
git status
git diff
git add nama-folder/
git diff --cached
git commit -m "Menambahkan latihan CRUD data wisata"
git push -u origin latihan-nama-topik
```

Ganti `nama-folder/` dan `latihan-nama-topik` sesuai folder serta branch Anda. Periksa perubahan sebelum commit, termasuk isi file baru yang belum muncul pada `git diff` biasa.

Buat **Pull Request** dengan tujuan `main`, lalu minta pembimbing atau pengelola repository memeriksa pekerjaan Anda.

### Memperbarui tugas pada branch yang sama

```bash
git add nama-folder/
git diff --cached
git commit -m "Memperbaiki validasi formulir"
git push
```

Jika terjadi konflik atau push ditolak, baca pesan Git dan koordinasikan dengan pembimbing. Hindari `git push --force` pada branch bersama.

## Kriteria Evaluasi Latihan

- **Ketepatan fungsi:** fitur memenuhi kebutuhan studi kasus.
- **Pemahaman:** peserta mampu menjelaskan algoritma dan kode.
- **Kualitas kode:** struktur jelas, konsisten, dan mudah dipelihara.
- **Tampilan:** antarmuka rapi serta mudah digunakan.
- **Validasi dan keamanan:** input diperiksa dan data dikelola dengan benar.
- **Ketepatan waktu:** pekerjaan selesai sesuai durasi latihan.
- **Dokumentasi:** cara menjalankan dan hasil pengujian dijelaskan.

Kriteria ini digunakan untuk evaluasi internal tim; penilaian lomba mengikuti ketentuan resmi panitia.

## Catatan Pengumpulan

Setiap tugas sebaiknya dilengkapi dengan:

- Nama peserta dan kelas.
- Judul serta tujuan latihan.
- Cara menjalankan aplikasi.
- Kebutuhan database dan file SQL berisi data contoh, jika digunakan.
- Fitur yang sudah selesai, hasil pengujian, dan kendala yang masih ditemukan.

## Tim dan Kontributor

Tim terdiri atas siswa/i peserta latihan SMK Jaya Buana dengan pendampingan guru pembimbing. Setiap anggota bertanggung jawab atas pekerjaan dan perkembangan belajarnya.

Kontribusi dicatat melalui riwayat commit. Gunakan identitas sendiri agar hasil pekerjaan dapat ditelusuri dengan jelas.

---

**SMK Jaya Buana — Disiplin Berlatih, Terampil Berkarya, Siap Berprestasi.**
