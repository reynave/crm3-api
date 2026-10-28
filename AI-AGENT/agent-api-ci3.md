# AI Agent Guide — CRM3 Backend API (CodeIgniter 3) — `api-v1`

> Dokumen ini adalah "otak" bagi AI agent agar memahami backend REST API project
> CRM3 (SalesTrax) di `C:\xampp7433\htdocs\crm.co.id\api-v1`.
> Framework: **CodeIgniter 3** (PHP 7.4.33, MySQL). Untuk detail framework,
> cukup rujuk dokumentasi resmi: **https://codeigniter.com/userguide3/**.
> Dokumen ini fokus menganalisa `application/` (controllers, models, libraries,
> views, config) dan pola penulisan kodenya.

---

## 1. Ringkasan

`api-v1` adalah **backend REST API** yang melayani frontend Angular 8 (`crm3-ng8`).
Satu endpoint = satu method di controller (`Class/method`), mengembalikan **JSON**.
Autentikasi memakai **JWT** (library `firebase/php-jwt` via Composer).

Alur umum permintaan:

```
Frontend (Angular) → GET/POST http://.../api-v1/<controller>/<method>[/param]
                    → Controller (CI3) → Model.php / Query Builder → MySQL
                    → json_encode({ "error": 0, "result": { ... } })
```

---

## 2. Lingkungan & Stack

| Aspek | Nilai |
| --- | --- |
| Framework | CodeIgniter 3 (lihat `api-v1/index.php`, `system/`) |
| PHP | 7.4.33 |
| Database | MySQL, driver `mysqli`, database `crm.co.id` |
| Tabel | prefix `crm3_*` |
| Autentikasi | JWT (HS256), library `firebase/php-jwt` (Composer) |
| Timezone | `Asia/Jakarta`, `SET time_zone = '+07:00'` |
| Output | JSON (`json_encode`), sebagian `application/javascript` |
| URL dev | `https://localhost:7344/crm.co.id/api-v1/` (lihat `config.php`) |
| URL prod | `https://s01.crm.co.id/api-v1/` (lihat `LIVE/config/config.php`) |

Composer (lihat `api-v1/composer.json`) dipakai untuk autoload `firebase/php-jwt`
(vendor ada di `api-v1/vendor/`).

---

## 3. Struktur Direktori `application/`

```
application/
├── .htaccess / index.html
├── cache/                 # (cache CI)
├── logs/                  # (log CI)
├── config/
│   ├── config.php         # base_url, index_page, dsb.
│   ├── database.php       # koneksi MySQL + custom key (openAPI, key)
│   ├── autoload.php       # autoload library/helper/model
│   ├── jwt.php            # secret JWT, issuer, expire
│   ├── routes.php         # default_controller = home
│   ├── hooks.php, constants.php, migration.php, mimes.php, dsb.
├── controllers/           # semua endpoint (lihat §9)
├── core/                  # (kosong, default CI)
├── helpers/               # (kosong)
├── hooks/                 # (kosong)
├── language/english/
├── libraries/
│   ├── Jwt_lib.php        # generate + validate JWT
│   ├── Ssp.php            # DataTables server-side processing
│   └── Ssp - ory.php      # backup, ABAIKAN
├── models/
│   ├── Model.php          # model utama (helper global + query)
│   ├── Model.php.ver1     # backup, ABAIKAN
│   └── User_model.php     # model user (login)
├── third_party/
└── views/
    ├── welcome_message.php
    ├── errors/            # template error CI
    ├── email/             # resetpassword, forgetpassword, submit
    └── output/quote.php   # template HTML cetak quotation
```

> File **backup/duplikat yang harus diabaikan** AI agent:
> - `controllers/Activity - ver 1.php`
> - `controllers/Dashboard.php.backup_ver1`
> - `controllers/Product-product.php`
> - `models/Model.php.ver1`
> - `libraries/Ssp - ory.php`

---

## 4. Routing & URL

- `.htaccess` (root `api-v1`) menulis ulang URL ke `index.php` (RewriteBase
  `/crm.co.id/api-v1/`, `index.php?/$1`), dan `config.php` set `index_page = ''`
  sehingga URL bersih tanpa `index.php`.
- `config.php` → `$config['uri_protocol'] = 'REQUEST_URI'`.
- `routes.php` → `$route['default_controller'] = 'home'`, tanpa custom route lain.
- Pola URL: `<base_url>/<controller>/<method>/<param>`.
  Contoh: `Lead/index`, `Lead/detail/12`, `Quote/print/5`, `Auth/login`.

---

## 5. Konfigurasi Penting

### `config/database.php`

```php
$db['default'] = array(
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'crm.co.id',
    'dbdriver' => 'mysqli',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'openAPI'  => TRUE,   // custom: flag apakah wajib validasi token
    'key'      => 'mXTSxr...', // custom: static API key
);
```

> `openAPI` dan `key` adalah key **kustom** yang ditambahkan ke array koneksi DB,
> dibaca via `$this->db->openAPI` dan `$this->db->key` di controller.

### `config/autoload.php`

- Libraries: `database`, `parser`, `session`, `email`
- Helpers: `html`, `url`, `form`, `directory`
- Models: `model` (jadi `$this->model` tersedia otomatis di semua controller)

### `config/jwt.php`

```php
$config['jwt_secret'] = 'GANTI_DENGAN_SECRET_KEY_YANG_PANJANG_DAN_RANDOM';
$config['jwt_issuer'] = 'crm3-api';
$config['jwt_expire'] = 3600 * 8; // 8 jam
```

---

## 6. Autentikasi (JWT)

### Library `libraries/Jwt_lib.php`

Membungkus `Firebase\JWT\JWT`:

- `generate_token($payload_data)` → encode payload `{ iss, iat, exp, data: $payload_data }`
  dengan algoritma **HS256**.
- `validate_token($token)` → decode; return `(array) $decoded` atau `false`.

### Proses login — `controllers/Auth.php`

1. `Auth/login` menerima POST JSON `{ username, password }`.
2. Validasi: method POST, username/password wajib, user ada, status aktif,
   password cocok (`md5($password) === $user->password`).
3. Ambil `access_rules` user, susun payload JWT (id, email, id_user_access,
   id_user_group, id_parent, folder, access_rules).
4. Generate token, kembalikan:

```json
{ "token": "...", "user": { "id":1, "email":"...", "first_name":"...", "last_name":"...", "code":"..." } }
```

### Validasi token di controller

Controller yang butuh auth memanggil di `__construct`:

```php
if (!$this->model->header($this->openAPI)) {
    echo $this->model->error("Error auth");
    exit;
}
```

`Model::header($openAPI)` membaca header `Token` (lowercase via `getallheaders()`),
lalu `Jwt_lib::validate_token()`. Jika `openAPI` false atau tanpa token → `false`.

`Model::id_user()` mengambil `$decoded['data']->id` (id user aktif dari token).

> Catatan: sebagian controller (mis. `Lead`, `Contact`, `Company`, `Dashboard`)
> **meng-comment** blok validasi `header()` — artinya endpoint tersebut sementara
> tidak diproteksi token di sisi server. AI agent perlu menyadari inkonsistensi ini.

---

## 7. Model Utama — `models/Model.php`

`Model.php` adalah **satu model global** (auto-loaded sebagai `$this->model`) berisi
helper query + logika bisnis. Method penting:

| Method | Fungsi |
| --- | --- |
| `select($field,$table,$where)` | SELECT 1 nilai, return nilai field |
| `crm3($field,$table,$where)` | Sama, tapi otomatis prefix `crm3_` pada tabel |
| `error($note)` | JSON `{ error:400, note }` |
| `key()` | Selalu `true` (stub) |
| `checkKey()` | Selalu `true` (stub) |
| `header($openAPI)` | Validasi token JWT dari header `Token` |
| `id_user()` | Ambil id user dari token JWT |
| `token($token)` | Cek token lama di tabel `user` |
| `number($name)` | Generate running number (`crm3_nu...`) |
| `currency($column)` | Ambil nilai dari `crm3_currency` default |
| `user($id)`, `user_row($row)` | Format data user |
| `user_recursive($id_parent)`, `user_recursive_target(...)` | Hierarki user (parent-child) |
| `recr_target_amount(...)` | Rekursif hitung target tim |
| `access_rules($id_user_access)` | Hak akses modul → `{ id_user_access, access_rules }` |
| `user_group($field,$id)` | Info group + `query` filter akses data (all_access/sharing_team_access) |
| `access_group($id,$field)` | Rekursif bangun query anggota tim |
| `allow_access_data($id)` | Stub, selalu `true` |
| `same_team($id_user)` | Cek satu tim (sharing_team_access) |
| `activity_person($id_module,$id)` | Nama objek aktivitas per tipe modul |
| `activity_history(...)` | Format riwayat aktivitas |
| `folder($path)` | Buat folder upload |
| `calculation($id)` | Hitung pricing (quote/so) |
| `lead($id)`, `contact($id)` | Ambil nama lead/contact |
| `https()`, `selected()`, `base64Decode()` | Util kecil |

### Pola query

- Query builder `$this->db->query(...)` + raw SQL untuk SELECT kompleks.
- `$this->db->insert/update/delete('tabel', $data, $where)` untuk tulis.
- Helper `$this->model->crm3('field','table','where')` dipakai luas untuk ambil 1 nilai.

---

## 8. Library

### `libraries/Ssp.php` (DataTables server-side)

Port resmi DataTables `ssp.class.php` (https://github.com/DataTables/DataTables).
Method static: `simple()`, `complex()`, `data_output()`, dst.

Dipanggil controller untuk endpoint list (contoh `Lead::index`):

```php
$this->load->library('ssp');
echo json_encode($this->ssp->simple($_GET, $sql_details, $table, $primaryKey, $columns));
```

- `$table` bisa raw string tabel atau **subquery** `( SELECT ... ) temp`.
- `$columns` = array `['db'=>kolom, 'dt'=>index]` (boleh tambah `formatter`).

### `libraries/Jwt_lib.php`

Lihat §6.

---

## 9. Pola Controller (Konvensi Penulisan)

Setiap controller `extends CI_Controller`, nama class **PascalCase** (camel, tidak
pakai underscore kecuali `Sales_order`, `Access_right`, `Company_class`,
`Report_sales_target`, `Opportunity_closed_reason`).

### 9.1 Blok `__construct` standar

```php
public function __construct()
{
    parent::__construct();
    $this->key     = $this->db->key;      // custom key dari database.php
    $this->openAPI = $this->db->openAPI;  // flag wajib token
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: key, token, Content-Type");
    header('Access-Control-Allow-Methods: GET, POST, PUT');
    header('Content-Type: application/javascript');
    date_default_timezone_set('Asia/Jakarta');
    $this->db->query("SET time_zone = '+07:00'");
    // (opsional) validasi token:
    if (!$this->model->header($this->openAPI)) { echo $this->model->error("Error auth"); exit; }
}
```

`Auth.php` sedikit berbeda (handle preflight `OPTIONS`, tidak wajib token).

### 9.2 Method CRUD standar

| Method | Fungsi |
| --- | --- |
| `index()` | List (via `ssp` DataTables atau raw query) |
| `detail($id)` | Detail 1 record + relasi |
| `insert()` | Tambah (POST) |
| `update()` | Ubah (POST) |
| `fn_delete()` | Hapus massal (POST, array `data`) |
| `fn_deleteSolo()` | Hapus satu |
| `edit($id)` | Data untuk form edit |
| `select()` / `selected()` / `getSelected()` | Dropdown / data pilihan |

### 9.3 Membaca input & menulis response

```php
// input body JSON (POST/PUT)
$post = json_decode(file_get_contents('php://input'), true);

// response sukses
$json = array("error" => 0, "result" => array( ... ));
echo json_encode($json);

// response gagal
echo $this->model->error("note");
```

- Loop `$this->db->query(...)->result()` lalu bangun array `$temp` + `array_push`.
- Casting eksplisit: `(int)`, `(float)`, `(bool)` pada nilai numerik/boolean.
- URL lampiran di-normalisasi `str_replace('./', base_url(), $row->url)`.

### 9.4 Konvensi penamaan method action

- `fn_...` untuk aksi backend yang dipanggil frontend (`fn_delete`, `fn_deleteSolo`,
  `fnUpdate`, `fn_updateLeadStatus`).
- `on...` untuk beberapa aksi (`onInsert`, `onSubmit`).
- Method public dengan nama kebab tidak dipakai; gunakan camelCase.

---

## 10. Daftar Controller & Endpoint

> `[t]` = memvalidasi token (`$this->model->header`) di `__construct`.

| Controller | Endpoint utama | Auth |
| --- | --- | --- |
| `Auth` | `Auth/login`, `Auth/user`, `Auth/access_rules` | publik (login) |
| `Home` | `Home/index` (welcome) | publik |
| `Login` | `Login/index` (redirect ke ../login/) | — |
| `Lead` | `Lead/index` (ssp), `detail`, `insert`, `update`, `convert`, `fn_delete`, `fn_updateLeadStatus`, `fn_deleteProduct` | sebagian |
| `Contact` | `select`, `index`, `getSelected`, `detail`, `insert`, `update`, `selected`, `widget_new_contact`, `fn_delete`, `fn_deleteSolo`, `edit` | sebagian |
| `Company` | `index`, `detail`, `insert`, `update`, `fn_delete`, `fn_deleteSolo`, `edit` | sebagian |
| `Opportunity` | `index`, `detail`, `insert`, `update`, `fn_delete`, `edit`, `new` | sebagian |
| `Deal` | `index`, `detail`, `update`, `fn_delete` | [t] |
| `Lost` | `index`, `detail`, `update` | [t] |
| `Quote` | `index`, `detail`, `insert`, `update`, `print`, `fn_delete` | [t] |
| `Sales_order` | `index`, `detail`, `create`, `update`, `print`, `fn_delete` | [t] |
| `Pricelist` | `index`, `detail`, `insert`, `update`, `fn_delete` | [t] |
| `Product` | `index`, `detail`, `insert`, `update`, `fn_delete` | [t] |
| `User` | `index`, `detail`, `insert`, `update`, `fn_delete`, `edit` | [t] |
| `Access_right` | `index`, `auth`, `detail`, `update`, `sync`, `onSubmit` | [t] |
| `Access_data` | `index`, `insert`, `update`, `fn_delete` | [t] |
| `Account` | `index`, `fnUpdate` | [t] |
| `Activity` | `index`, `httpGet`, `httpHistory`, `onInsert`, `fn_remove` | [t] |
| `Converted` | `index`, `detail` | [t] |
| `Profile` | `index`, `update` (profil user) | [t] |
| `Dashboard` | `index` (statistik + chart) | sebagian |
| `Report_sales_target` | `index` (report target) | [t] |
| `Branch` | (data cabang) | [t] |
| `Currency` | `index`, `insert` | [t] |
| `Industry` | `index`, `insert` | [t] |
| `Leadsource` | `index`, `insert` | [t] |
| `Classmaster` | `index`, `insert` | [t] |
| `Company_class` | `index`, `price_list`, `update_price`, `erase_price` | [t] |
| `Opportunity_closed_reason` | `index`, `insert` | [t] |
| `Output` | `requestToken`, `quote` (generate HTML cetak) | [t] |
| `Upload` | `attachment`, `quotes_attachment` (upload file) | cek `post('token')` |
| `Datatables` | `index`, `companies` (contoh ssp) | — |

> `Product-product.php`, `Activity - ver 1.php`, `Dashboard.php.backup_ver1`
> adalah file backup — jangan dijadikan acuan.

---

## 11. Format Response & Database

### Format response standar

```json
{ "error": 0, "result": { ... } }
```

- `error = 0` berarti sukses; `error = 400` berarti gagal (via `Model::error()`).
- Beberapa endpoint menyisipkan struktur `result` dengan key beragam sesuai modul
  (`lead`, `contact`, `user_access`, `attachment`, `pricing`, `quote_item`, dst.).
- `Auth/login` dan `Output/quote` (mode non-json) punya format khusus.

### Database (MySQL, prefix `crm3_`)

Tabel yang tampak dipakai (berdasarkan query di controller/model):

- `crm3_user`, `crm3_user_access`, `crm3_user_access_rules`, `crm3_user_group`,
  `crm3_user_target`, `crm3_user_auth`
- `crm3_lead`, `crm3_lead_status`, `crm3_lead_source`, `crm3_title`, `crm3_industry`
- `crm3_contact`, `crm3_company`, `crm3_company_class`
- `crm3_opportunity`, `crm3_opportunity_stage`, `crm3_opportunity_closed_reason`
- `crm3_quote`, `crm3_quote_detail`, `crm3_quote_status`, `crm3_deal`
- `crm3_sales_order`, `crm3_sales_order_detail`
- `crm3_product`, `crm3_price_list`, `crm3_currency`, `crm3_class`
- `crm3_activity`, `crm3_activity_type`
- `crm3_attachment`, `crm3_output`, `crm3_module`, `crm3_global`, `crm3_number`

> Soft-delete memakai kolom `presence` (1 = aktif, 0 = terhapus), bukan DELETE fisik.
> Audit field umum: `input_date`, `input_by`, `update_date`, `update_by`.

---

## 12. View

View non-standar (selain template error CI):

- `views/output/quote.php` — template HTML untuk mencetak quotation. Dipakai
  `Output::quote` → render ke file `.html` di `./upload/quote/...` lalu record
  disimpan ke tabel `crm3_output`.
- `views/email/` — `resetpassword.php`, `forgetpassword.php`, `resetpassword_submit.php`
  (template email reset password).
- `views/welcome_message.php` — halaman default `Home`.

---

## 13. Panduan untuk AI Agent (Do & Don't)

### Yang HARUS diikuti

1. Controller baru: `extends CI_Controller`, letakkan di `application/controllers/`.
2. Salin pola `__construct` standar (§9.1): CORS headers, timezone, dan validasi
   token bila endpoint butuh auth.
3. Response pakai `json_encode([ "error" => 0, "result" => ... ])`.
4. Baca body JSON dengan `json_decode(file_get_contents('php://input'), true)`.
5. Akses DB via `$this->db` (Query Builder / raw query) atau helper `$this->model->crm3(...)`.
6. Query listing gunakan `ssp` untuk DataTables server-side (pola `Lead::index`).
7. Semua tabel pakai prefix `crm3_`; soft-delete via kolom `presence`.
8. Set audit field `input_date/input_by/update_date/update_by` saat insert/update.
9. Casting `(int)/(float)/(bool)` untuk nilai numerik/boolean di response.
10. Jangan menyentuh `system/` (core CI) — cukup rujuk docs resmi CI3.

### Yang JANGAN dilakukan

1. Jangan hapus/ubah helper global `Model.php` tanpa memeriksa pemakaiannya di
   seluruh controller.
2. Jangan lupa prefix `crm3_` saat query tabel bisnis.
3. Jangan pakai DELETE fisik — gunakan update `presence = 0`.
4. Jangan ubah nama kolom/struktur response yang sudah dipakai frontend Angular.
5. Jangan jadikan file `*- ver 1.php`, `*.backup_ver1`, `*- Copy.*`, `* - ory.php`,
   `Model.php.ver1` sebagai acuan.

---

## 14. Dokumentasi Framework

Untuk detail CodeIgniter 3 (Query Builder, Routing, Library, Helper, dsb.),
lihat dokumentasi resmi: **https://codeigniter.com/userguide3/**.

---

*Dokumen ini dihasilkan dari analisis langsung kode di
`C:\xampp7433\htdocs\crm.co.id\api-v1\application`.*



