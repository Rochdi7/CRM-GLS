<?php
foreach (App\Models\User::with('roles')->orderBy('id')->get() as $u) {
    echo str_pad((string) $u->email, 34).' | '
        .str_pad((string) $u->username, 24).' | '
        .str_pad($u->roles->pluck('name')->implode(',') ?: '-', 22).' | '
        .'active='.($u->is_active ? 'yes' : 'no')
        .' | must_change='.($u->must_change_password ? 'yes' : 'no').PHP_EOL;
}
