<?php

/** Manajemen pengguna (khusus admin; lihat app/routes.php). */
class AdminUserController extends Controller
{
    public function index(): void
    {
        $q = trim((string) $this->input('q', ''));
        $role = (string) $this->input('role', '');
        $status = (string) $this->input('status', '');
        $this->view('admin/users/index', [
            'title'  => 'Pengguna',
            'result' => User::paginate($q, $role, $status, (int) $this->input('halaman', 1)),
            'q' => $q, 'role' => $role, 'status' => $status,
        ], 'admin');
    }

    public function create(): void
    {
        $this->view('admin/users/form', ['title' => 'Tambah Pengguna', 'user' => null, 'self' => false], 'admin');
    }

    public function store(): void
    {
        [$data, $errors] = $this->collect(null);
        $password = (string) ($_POST['password'] ?? '');
        if ($password === '') {
            $errors['password'] = 'Kata sandi wajib diisi.';
        } elseif ($msg = $this->passwordError($password)) {
            $errors['password'] = $msg;
        }

        if ($errors) {
            $this->fail($errors, '/admin/pengguna/tambah');
        }
        $id = User::create($data['nama'], $data['email'], $data['no_hp'], $password, $data['role']);
        log_activity('tambah_pengguna', 'user#' . $id, $data['email'] . ' (' . $data['role'] . ')');
        flash('success', 'Pengguna ditambahkan.');
        redirect('/admin/pengguna');
    }

    public function edit(string $id): void
    {
        $user = User::find((int) $id) ?? abort(404);
        $this->view('admin/users/form', [
            'title' => 'Ubah Pengguna',
            'user'  => $user,
            'self'  => (int) $user['id'] === Auth::id(),
        ], 'admin');
    }

    public function update(string $id): void
    {
        $user = User::find((int) $id) ?? abort(404);
        $self = (int) $user['id'] === Auth::id();
        if ($self) {
            $_POST['role'] = $user['role']; // select dinonaktifkan di form sehingga tidak terkirim
        }
        [$data, $errors] = $this->collect($user);

        // Admin tidak dapat mengubah peran atau menonaktifkan akunnya sendiri (mencegah terkunci).
        if ($self) {
            $data['role'] = $user['role'];
            $data['status'] = $user['status'];
        }

        $password = (string) ($_POST['password'] ?? '');
        if ($password !== '' && ($msg = $this->passwordError($password))) {
            $errors['password'] = $msg;
        }
        if ($errors) {
            $this->fail($errors, "/admin/pengguna/{$user['id']}/ubah");
        }

        User::update((int) $user['id'], $data);
        $changes = [];
        foreach (['role', 'status', 'email'] as $k) {
            if ($data[$k] !== $user[$k]) {
                $changes[] = "$k: {$user[$k]} -> {$data[$k]}";
            }
        }
        if ($password !== '') {
            User::setPassword((int) $user['id'], $password);
            $changes[] = 'kata sandi direset';
        }
        log_activity('ubah_pengguna', 'user#' . $user['id'], implode('; ', $changes));
        flash('success', 'Pengguna diperbarui.');
        redirect('/admin/pengguna');
    }

    /** Validasi isian umum. @return array{0: array, 1: array} [data, errors] */
    private function collect(?array $existing): array
    {
        $v = new Validator($_POST, [
            'nama'  => 'required|max:100',
            'email' => 'required|email|max:100',
            'no_hp' => 'phone',
            'role'  => 'required|in:' . implode(',', array_keys(User::ROLES)),
        ], ['nama' => 'Nama', 'email' => 'Email', 'no_hp' => 'No. HP', 'role' => 'Peran']);
        $errors = $v->errors();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!isset($errors['email']) && User::emailTaken($email, (int) ($existing['id'] ?? 0))) {
            $errors['email'] = 'Email sudah dipakai pengguna lain.';
        }

        $data = [
            'nama'   => trim((string) ($_POST['nama'] ?? '')),
            'email'  => $email,
            'no_hp'  => trim((string) ($_POST['no_hp'] ?? '')),
            'role'   => (string) ($_POST['role'] ?? ''),
            'status' => $existing === null ? 'aktif' : (($_POST['status'] ?? '') === 'nonaktif' ? 'nonaktif' : 'aktif'),
        ];
        return [$data, $errors];
    }

    private function passwordError(string $password): ?string
    {
        if (mb_strlen($password) < 8) {
            return 'Kata sandi minimal 8 karakter.';
        }
        if (mb_strlen($password) > 72) {
            return 'Kata sandi maksimal 72 karakter.';
        }
        return null;
    }

    private function fail(array $errors, string $back): never
    {
        with_old($_POST);
        foreach ($errors as $msg) {
            flash('error', $msg);
        }
        redirect($back);
    }
}
