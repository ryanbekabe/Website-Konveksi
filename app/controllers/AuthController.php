<?php

class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->view('auth/login', ['title' => 'Masuk']);
    }

    public function login(): void
    {
        $email = strtolower(trim((string) $this->input('email', '')));
        $password = (string) $this->input('password', '');

        if (Auth::tooManyAttempts($email)) {
            flash('error', 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.');
            redirect('/masuk');
        }

        if (!Auth::attempt($email, $password)) {
            with_old(['email' => $email]);
            flash('error', 'Email atau kata sandi salah.');
            redirect('/masuk');
        }

        $intended = Session::pull('intended');
        // Hanya izinkan redirect ke path internal.
        if (is_string($intended) && preg_match('#^/(?!/)#', $intended)) {
            $base = base_path();
            if ($base !== '' && str_starts_with($intended, $base)) {
                $intended = substr($intended, strlen($base)) ?: '/';
            }
            redirect($intended);
        }
        redirect(Auth::hasRole(['admin', 'produksi', 'keuangan']) ? '/admin' : '/');
    }

    public function registerForm(): void
    {
        $this->view('auth/register', ['title' => 'Daftar Akun']);
    }

    public function register(): void
    {
        $v = new Validator($_POST, [
            'nama'     => 'required|max:100',
            'email'    => 'required|email|max:100',
            'no_hp'    => 'required|phone',
            'password' => 'required|min:8|max:72',
        ], ['nama' => 'Nama', 'email' => 'Email', 'no_hp' => 'No. HP', 'password' => 'Kata sandi']);

        $errors = $v->errors();
        if (!isset($errors['email']) && User::findByEmail((string) $_POST['email'])) {
            $errors['email'] = 'Email sudah terdaftar.';
        }
        if (!isset($errors['password']) && ($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? null)) {
            $errors['password_confirm'] = 'Konfirmasi kata sandi tidak sama.';
        }

        if ($errors) {
            with_old($_POST);
            foreach ($errors as $msg) {
                flash('error', $msg);
            }
            redirect('/daftar');
        }

        $id = User::create(trim($_POST['nama']), trim($_POST['email']), trim($_POST['no_hp']), $_POST['password']);
        Session::regenerate();
        Session::set('user_id', $id);
        flash('success', 'Pendaftaran berhasil. Selamat datang!');
        redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::start();
        flash('success', 'Anda telah keluar.');
        redirect('/');
    }
}
