<?php

/** Akun saya: ubah profil dan kata sandi (semua pengguna yang login). */
class AccountController extends Controller
{
    public function form(): void
    {
        $this->view('account/index', ['title' => 'Akun Saya', 'user' => Auth::user()]);
    }

    public function updateProfile(): void
    {
        $v = new Validator($_POST, ['nama' => 'required|max:100', 'no_hp' => 'phone'], ['nama' => 'Nama', 'no_hp' => 'No. HP']);
        if ($v->fails()) {
            with_old($_POST);
            foreach ($v->errors() as $msg) {
                flash('error', $msg);
            }
            redirect('/akun');
        }
        User::update((int) Auth::id(), ['nama' => trim($_POST['nama']), 'no_hp' => trim((string) ($_POST['no_hp'] ?? ''))]);
        flash('success', 'Profil diperbarui.');
        redirect('/akun');
    }

    public function updatePassword(): void
    {
        $user = Auth::user();
        $current = (string) ($_POST['password_lama'] ?? '');
        $new = (string) ($_POST['password_baru'] ?? '');
        $confirm = (string) ($_POST['password_konfirmasi'] ?? '');

        $error = null;
        if (!password_verify($current, $user['password_hash'])) {
            $error = 'Kata sandi saat ini salah.';
        } elseif (mb_strlen($new) < 8 || mb_strlen($new) > 72) {
            $error = 'Kata sandi baru harus 8 sampai 72 karakter.';
        } elseif ($new !== $confirm) {
            $error = 'Konfirmasi kata sandi baru tidak sama.';
        } elseif ($new === $current) {
            $error = 'Kata sandi baru harus berbeda dari yang lama.';
        }
        if ($error) {
            flash('error', $error);
            redirect('/akun');
        }

        User::setPassword((int) $user['id'], $new);
        Session::regenerate();
        log_activity('ganti_sandi', 'user#' . $user['id']);
        flash('success', 'Kata sandi berhasil diganti.');
        redirect('/akun');
    }
}
