<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaksi extends CreateRecord
{
    protected static string $resource = TransaksiResource::class;

    protected static bool $canCreateAnother = false;
    protected static ?string $title = 'Tambah Transaksi';
    
    protected function getRedirectUrl(): string
    {
        return TransaksiResource::getUrl('index');
    }

    protected function getCreateButtonLabel(): string
    {
        return 'Tambah Data';
    }
}
