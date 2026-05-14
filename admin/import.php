<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$results = [];
$summary = [
    'inserted' => 0,
    'updated' => 0,
    'failed' => 0,
];
$error = '';

function import_template_rows(): array
{
    return [
        ['nama', 'nisn', 'tanggal_lahir', 'kelas', 'status_kelulusan', 'mode_drama', 'pesan_khusus'],
        ['Ahmad Fulan', '1234567890', '2011-05-14', 'IX A', 'LULUS', 'Drama Lucu', 'Selamat, teruslah berprestasi.'],
        ['Siti Aminah', '1234567891', '2011-07-21', 'IX B', 'LULUS', 'Drama Ringan', 'Teruslah belajar dan jaga akhlak mulia.'],
    ];
}

function download_template_csv()
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template-import-siswa.csv"');

    $out = fopen('php://output', 'w');
    foreach (import_template_rows() as $row) {
        fputcsv($out, $row);
    }
    fputcsv($out, []);
    fputcsv($out, ['mode_drama_valid']);
    foreach (drama_modes() as $mode) {
        fputcsv($out, [$mode]);
    }
    fclose($out);
    exit;
}

function xml_value(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsx_column_name(int $index): string
{
    $name = '';
    while ($index > 0) {
        $index--;
        $name = chr(65 + ($index % 26)) . $name;
        $index = intdiv($index, 26);
    }
    return $name;
}

function xlsx_sheet_xml(array $rows, string $dimension, bool $withValidation = false): string
{
    $xmlRows = '';
    foreach ($rows as $rowIndex => $row) {
        $excelRow = $rowIndex + 1;
        $xmlRows .= '<row r="' . $excelRow . '">';
        foreach ($row as $colIndex => $value) {
            $cell = xlsx_column_name($colIndex + 1) . $excelRow;
            $style = $excelRow === 1 ? ' s="1"' : '';
            $xmlRows .= '<c r="' . $cell . '" t="inlineStr"' . $style . '><is><t>' . xml_value((string) $value) . '</t></is></c>';
        }
        $xmlRows .= '</row>';
    }

    $validation = '';
    if ($withValidation) {
        $validation = '<dataValidations count="1"><dataValidation type="list" allowBlank="1" sqref="F2:F2000"><formula1>mode_drama!$A$2:$A$6</formula1></dataValidation></dataValidations>';
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<dimension ref="' . $dimension . '"/>'
        . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="18"/>'
        . '<cols><col min="1" max="1" width="28" customWidth="1"/><col min="2" max="2" width="18" customWidth="1"/><col min="3" max="3" width="18" customWidth="1"/><col min="4" max="4" width="12" customWidth="1"/><col min="5" max="5" width="18" customWidth="1"/><col min="6" max="6" width="24" customWidth="1"/><col min="7" max="7" width="52" customWidth="1"/></cols>'
        . '<sheetData>' . $xmlRows . '</sheetData>'
        . $validation
        . '</worksheet>';
}

function build_zip_file(array $files): string
{
    $data = '';
    $central = '';
    $offset = 0;

    foreach ($files as $name => $content) {
        $crc = (int) sprintf('%u', crc32($content));
        $size = strlen($content);
        $nameLength = strlen($name);

        $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $size, $size, $nameLength, 0);
        $data .= $localHeader . $name . $content;

        $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset)
            . $name;
        $offset += strlen($localHeader) + $nameLength + $size;
    }

    $centralSize = strlen($central);
    $entries = count($files);
    $end = pack('VvvvvVVv', 0x06054b50, 0, 0, $entries, $entries, $centralSize, $offset, 0);

    return $data . $central . $end;
}

function download_template_xlsx()
{
    $modeRows = [['mode_drama_valid']];
    foreach (drama_modes() as $mode) {
        $modeRows[] = [$mode];
    }

    $files = [
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>',
        'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Portal Kelulusan MTsN 1 Pohuwato</Application></Properties>',
        'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Template Import Siswa</dc:title><dc:creator>Portal Kelulusan MTsN 1 Pohuwato</dc:creator></cp:coreProperties>',
        'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="data_siswa" sheetId="1" r:id="rId1"/><sheet name="mode_drama" sheetId="2" r:id="rId2"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
        'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><color rgb="FF18352D"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0B5A42"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
        'xl/worksheets/sheet1.xml' => xlsx_sheet_xml(import_template_rows(), 'A1:G3', true),
        'xl/worksheets/sheet2.xml' => xlsx_sheet_xml($modeRows, 'A1:A6', false),
    ];

    $xlsx = build_zip_file($files);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="template-import-siswa.xlsx"');
    header('Content-Length: ' . strlen($xlsx));
    echo $xlsx;
    exit;
}

function valid_import_date(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
}

function process_student_import_row(array $row, int $line, bool $updateExisting): array
{
    $name = trim((string) ($row[0] ?? ''));
    $nisn = trim((string) ($row[1] ?? ''));
    $birthDate = trim((string) ($row[2] ?? ''));
    $class = trim((string) ($row[3] ?? ''));
    $status = graduation_status((string) ($row[4] ?? 'LULUS'));
    $rawMode = trim((string) ($row[5] ?? 'Normal'));
    $mode = normalize_drama_mode($rawMode);
    $message = trim(implode(',', array_slice($row, 6)));

    if ($name === '' && $nisn === '' && $birthDate === '' && $class === '') {
        return ['status' => 'skip', 'line' => $line, 'message' => 'Baris kosong dilewati.'];
    }

    if ($name === '') {
        return ['status' => 'failed', 'line' => $line, 'message' => 'Nama tidak boleh kosong.'];
    }

    if ($nisn === '') {
        return ['status' => 'failed', 'line' => $line, 'message' => 'NISN tidak boleh kosong.'];
    }

    if (!valid_import_date($birthDate)) {
        return ['status' => 'failed', 'line' => $line, 'message' => 'Tanggal lahir harus format YYYY-MM-DD dan valid.'];
    }

    if ($class === '') {
        return ['status' => 'failed', 'line' => $line, 'message' => 'Kelas tidak boleh kosong.'];
    }

    if ($rawMode !== '' && $rawMode !== 'Drama Super Tegang tapi Tetap Aman' && !in_array($rawMode, drama_modes(), true)) {
        return ['status' => 'failed', 'line' => $line, 'message' => 'Mode drama tidak valid. Gunakan Normal, Drama Ringan, Drama Sedang, Drama Lucu, atau Drama Super Tegang.'];
    }

    $pdo = pdo();
    $check = $pdo->prepare('SELECT id FROM students WHERE nisn = :nisn LIMIT 1');
    $check->execute(['nisn' => $nisn]);
    $existingId = $check->fetchColumn();

    if ($existingId && !$updateExisting) {
        return ['status' => 'failed', 'line' => $line, 'message' => 'NISN sudah ada. Aktifkan opsi perbarui data lama jika ingin menimpa.'];
    }

    if ($existingId) {
        $stmt = $pdo->prepare(
            'UPDATE students
             SET nama = :nama,
                 tanggal_lahir = :tanggal_lahir,
                 kelas = :kelas,
                 status_kelulusan = :status_kelulusan,
                 mode_drama = :mode_drama,
                 pesan_khusus = :pesan_khusus
             WHERE nisn = :nisn'
        );
        $stmt->execute([
            'nama' => $name,
            'tanggal_lahir' => $birthDate,
            'kelas' => $class,
            'status_kelulusan' => $status,
            'mode_drama' => $mode,
            'pesan_khusus' => $message,
            'nisn' => $nisn,
        ]);

        return ['status' => 'updated', 'line' => $line, 'message' => 'Data diperbarui untuk NISN ' . $nisn . '.'];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO students (nama, nisn, tanggal_lahir, kelas, status_kelulusan, mode_drama, pesan_khusus)
         VALUES (:nama, :nisn, :tanggal_lahir, :kelas, :status_kelulusan, :mode_drama, :pesan_khusus)'
    );
    $stmt->execute([
        'nama' => $name,
        'nisn' => $nisn,
        'tanggal_lahir' => $birthDate,
        'kelas' => $class,
        'status_kelulusan' => $status,
        'mode_drama' => $mode,
        'pesan_khusus' => $message,
    ]);

    return ['status' => 'inserted', 'line' => $line, 'message' => 'Data baru ditambahkan untuk NISN ' . $nisn . '.'];
}

function xlsx_column_index_from_cell(string $cellRef): int
{
    $letters = preg_replace('/[^A-Z]/', '', strtoupper($cellRef));
    $index = 0;

    for ($i = 0, $length = strlen((string) $letters); $i < $length; $i++) {
        $index = ($index * 26) + (ord($letters[$i]) - 64);
    }

    return max(0, $index - 1);
}

function xlsx_node_text($node): string
{
    $node->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $textNodes = $node->xpath('.//m:t') ?: [];

    if ($textNodes) {
        $text = '';
        foreach ($textNodes as $textNode) {
            $text .= (string) $textNode;
        }
        return $text;
    }

    return (string) $node;
}

function read_basic_xlsx_rows(string $path): array
{
    if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
        throw new RuntimeException('Import XLSX memerlukan PhpSpreadsheet, atau ekstensi ZIP dan SimpleXML. Jika belum tersedia, gunakan Template CSV.');
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('File XLSX tidak dapat dibuka.');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $shared = simplexml_load_string($sharedXml);
        if ($shared !== false) {
            $shared->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach (($shared->xpath('//m:si') ?: []) as $si) {
                $text = xlsx_node_text($si);
                $sharedStrings[] = $text;
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($sheetXml === false) {
        throw new RuntimeException('Sheet pertama XLSX tidak ditemukan.');
    }

    $sheet = simplexml_load_string($sheetXml);
    if ($sheet === false) {
        throw new RuntimeException('Sheet XLSX tidak dapat dibaca.');
    }

    $rows = [];
    $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    foreach (($sheet->xpath('//m:sheetData/m:row') ?: []) as $row) {
        $row->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $cells = [];
        foreach (($row->xpath('m:c') ?: []) as $cell) {
            $cell->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $attributes = $cell->attributes();
            $cellRef = (string) ($attributes['r'] ?? '');
            $type = (string) ($attributes['t'] ?? '');
            $columnIndex = $cellRef !== '' ? xlsx_column_index_from_cell($cellRef) : count($cells);
            $value = '';

            if ($type === 's') {
                $valueNodes = $cell->xpath('m:v') ?: [];
                $stringIndex = (int) ($valueNodes[0] ?? 0);
                $value = $sharedStrings[$stringIndex] ?? '';
            } elseif ($type === 'inlineStr') {
                $inlineNodes = $cell->xpath('m:is') ?: [];
                $value = $inlineNodes ? xlsx_node_text($inlineNodes[0]) : '';
            } else {
                $valueNodes = $cell->xpath('m:v') ?: [];
                $value = (string) ($valueNodes[0] ?? '');
                if ($columnIndex === 2 && is_numeric($value)) {
                    $timestamp = ((float) $value - 25569) * 86400;
                    $value = gmdate('Y-m-d', (int) round($timestamp));
                }
            }

            $cells[$columnIndex] = $value;
        }

        if ($cells) {
            ksort($cells);
            $max = max(array_keys($cells));
            $ordered = [];
            for ($i = 0; $i <= $max; $i++) {
                $ordered[] = $cells[$i] ?? '';
            }
            $rows[] = $ordered;
        }
    }

    return $rows;
}

function read_xlsx_rows(string $path): array
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }

    if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        return $sheet->toArray(null, true, true, false);
    }

    return read_basic_xlsx_rows($path);
}

if (isset($_GET['template'])) {
    $template = (string) $_GET['template'];
    if ($template === 'csv') {
        download_template_csv();
    }
    if ($template === 'xlsx') {
        download_template_xlsx();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $updateExisting = isset($_POST['update_existing']);

    if (empty($_FILES['student_file']['tmp_name']) || !is_uploaded_file($_FILES['student_file']['tmp_name'])) {
        $error = 'Silakan pilih file CSV atau XLSX.';
    } else {
        $fileName = (string) ($_FILES['student_file']['name'] ?? '');
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['csv', 'xlsx'];

        if (!in_array($extension, $allowed, true)) {
            $error = 'Tipe file tidak valid. Hanya CSV dan XLSX yang diperbolehkan.';
        } else {
            try {
                $rows = [];
                if ($extension === 'csv') {
                    $handle = fopen($_FILES['student_file']['tmp_name'], 'r');
                    if ($handle === false) {
                        throw new RuntimeException('File CSV tidak dapat dibaca.');
                    }
                    while (($data = fgetcsv($handle, 0, ',')) !== false) {
                        $rows[] = $data;
                    }
                    fclose($handle);
                } else {
                    $rows = read_xlsx_rows($_FILES['student_file']['tmp_name']);
                }

                foreach ($rows as $index => $row) {
                    $line = $index + 1;
                    $firstCell = strtolower(trim((string) ($row[0] ?? '')));
                    if ($line === 1 && in_array($firstCell, ['nama', 'name'], true)) {
                        continue;
                    }

                    $nonEmptyCells = array_values(array_filter($row, static fn ($cell) => trim((string) $cell) !== ''));
                    if ($firstCell === 'mode_drama_valid' || (count($nonEmptyCells) === 1 && in_array((string) $nonEmptyCells[0], drama_modes(), true))) {
                        continue;
                    }

                    $result = process_student_import_row($row, $line, $updateExisting);
                    if ($result['status'] === 'skip') {
                        continue;
                    }

                    $results[] = $result;
                    if (isset($summary[$result['status']])) {
                        $summary[$result['status']]++;
                    }
                }
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }
}

admin_header('Import Data Siswa', 'import');
?>
<div class="page-heading">
    <div>
        <p class="admin-eyebrow">Import</p>
        <h1>Import Data Siswa</h1>
    </div>
    <a class="btn btn-outline-success" href="students.php">Kembali ke Data Siswa</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<section class="admin-card mb-4">
    <div class="card-title-row">
        <div>
            <h2>Template Import</h2>
            <p class="text-muted mb-0">Unduh template berisi header, contoh data siswa, dan daftar mode drama yang valid.</p>
        </div>
        <div class="template-actions">
            <a class="btn btn-admin-primary" href="import.php?template=xlsx">Template Excel</a>
            <a class="btn btn-outline-success" href="import.php?template=csv">Template CSV</a>
        </div>
    </div>
</section>

<section class="admin-card mb-4">
    <h2>Upload CSV/XLSX</h2>
    <p class="text-muted">Format kolom: nama, nisn, tanggal_lahir, kelas, status_kelulusan, mode_drama, pesan_khusus. Jika import XLSX tidak didukung hosting, gunakan Template CSV.</p>
    <form method="post" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div class="col-lg-7">
            <label class="form-label" for="student_file">File CSV atau XLSX</label>
            <input class="form-control" type="file" name="student_file" id="student_file" accept=".csv,.xlsx" required>
        </div>
        <div class="col-lg-5 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="update_existing" id="update_existing" checked>
                <label class="form-check-label" for="update_existing">Perbarui data jika NISN sudah ada</label>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-admin-primary" type="submit">Import Data</button>
        </div>
    </form>
</section>

<section class="admin-card mb-4">
    <h2>Contoh CSV</h2>
    <pre class="sample-code">nama,nisn,tanggal_lahir,kelas,status_kelulusan,mode_drama,pesan_khusus
Ahmad Fulan,1234567890,2011-05-14,IX A,LULUS,Drama Lucu,"Selamat, teruslah berprestasi."</pre>
    <p class="text-muted mb-0">Mode drama yang didukung: Normal, Drama Ringan, Drama Sedang, Drama Lucu, Drama Super Tegang.</p>
</section>

<?php if ($results): ?>
    <section class="admin-card">
        <div class="stats-grid compact mb-4">
            <article class="stat-card">
                <span>Ditambahkan</span>
                <strong><?= (int) $summary['inserted'] ?></strong>
            </article>
            <article class="stat-card">
                <span>Diperbarui</span>
                <strong><?= (int) $summary['updated'] ?></strong>
            </article>
            <article class="stat-card">
                <span>Gagal</span>
                <strong><?= (int) $summary['failed'] ?></strong>
            </article>
        </div>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                <tr>
                    <th>Baris</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?= (int) $result['line'] ?></td>
                        <td><span class="badge text-bg-<?= $result['status'] === 'failed' ? 'danger' : 'success' ?>"><?= h($result['status']) ?></span></td>
                        <td><?= h($result['message']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
<?php admin_footer(); ?>
