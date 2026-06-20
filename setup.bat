@echo off
REM Membuat folder utama
mkdir sklipci
cd sklipci

REM File di root
type nul > index.php

REM Folder config
mkdir config
type nul > config\config.php

REM Folder app dan subfoldernya
mkdir app
mkdir app\controller
mkdir app\model
mkdir app\view
mkdir app\view\partials
mkdir app\view\partials\admin
mkdir app\view\partials\mahasiswa
mkdir app\view\partials\dosen
mkdir app\view\public
mkdir app\view\admin
mkdir app\view\mahasiswa
mkdir app\view\dosen

REM File controller
type nul > app\controller\Controller.php
type nul > app\controller\PublicController.php
type nul > app\controller\AdminController.php
type nul > app\controller\MahasiswaController.php
type nul > app\controller\DosenController.php

REM File view
type nul > app\view\partials\header.php
type nul > app\view\partials\footer.php
type nul > app\view\partials\admin\sidebar.php
type nul > app\view\partials\mahasiswa\bottom-nav.php
type nul > app\view\partials\dosen\bottom-nav.php

type nul > app\view\public\home.php
type nul > app\view\admin\dashboard.php
type nul > app\view\mahasiswa\dashboard.php
type nul > app\view\dosen\dashboard.php

REM Folder public dan aset
mkdir public
mkdir public\css
mkdir public\js
mkdir public\uploads

type nul > public\css\style.css
type nul > public\js\app.js

echo Struktur folder berhasil dibuat!
pause
