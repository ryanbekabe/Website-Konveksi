<?php

class NotificationController extends Controller
{
    public function index(): void
    {
        $uid = (int) Auth::id();
        $items = Database::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50', [$uid]);
        $this->view('notifications/index', ['title' => 'Notifikasi', 'items' => $items]);
    }

    public function markRead(): void
    {
        Database::update('notifications', ['dibaca' => 1], 'user_id = ? AND dibaca = 0', [Auth::id()]);
        redirect('/notifikasi');
    }
}
