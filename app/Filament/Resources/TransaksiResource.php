<?php

namespace App\Filament\Resources;

use App\Exports\TransaksiExport;
use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\RelationManagers;
use App\Models\Transaksi;
use Carbon\Carbon;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Facades\Excel;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('waktu_transaksi');
    }

    protected static ?string $navigationLabel = 'Transaksi';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $activeNavigationIcon = 'heroicon-s-currency-dollar';

    protected static ?string $pluralModelLabel = 'Transaksi';

    public static function form(Form $form): Form
    {
         // Ambil waktu transaksi terakhir dari database
        $lastTransactionTime = Transaksi::latest('waktu_transaksi')->pluck('waktu_transaksi')->first();

        // Pastikan ada data
        if ($lastTransactionTime) {
            // Tambahkan 30 menit ke waktu transaksi terakhir
            $newTime = Carbon::parse($lastTransactionTime)->addMinutes(30);
            // Format waktu
            $formattedTime = $newTime->format('d M Y H:i');
        } else {
            // Jika tidak ada data transaksi, set waktu default (misalnya, sekarang)
            $formattedTime = Carbon::now()->addMinutes(30)->format('d M Y H:i');
        }
        return $form
            ->schema([
                TextInput::make('saldo_awal_hari_olibs')
                    ->label('Saldo Awal Hari OLIBS')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->default(fn (Get $get) => function () {
                        $today = Carbon::now()->format('Y-m-d');
                
                        $transaksiHariIni = Transaksi::whereDate('waktu_transaksi', $today)->first();
                
                        return $transaksiHariIni?->saldo_awal_hari_olibs;
                    }),

                TextInput::make('saldo_akhir_hari_berjalan_olibs')
                    ->label('Saldo Akhir Hari Berjalan OLIBS')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->reactive()
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $saldoAkhir = $get('saldo_akhir_hari_berjalan_olibs');
                        $cutOff = $get('cut_off_olibs');

                        if ($saldoAkhir && $cutOff) {
                            $kewajiban = $saldoAkhir - $cutOff;
                            $set('kewajiban_olibs', $kewajiban);
                        }
                    }),

                TextInput::make('cut_off_olibs')
                    ->label('Cut Off OLIBS')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->reactive()
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $saldoAkhir = $get('saldo_akhir_hari_berjalan_olibs');
                        $cutOff = $get('cut_off_olibs');

                        if ($saldoAkhir && $cutOff) {
                            $kewajiban = $saldoAkhir - $cutOff;
                            $set('kewajiban_olibs', $kewajiban);
                        }
                    })
                    ->default(fn (Get $get) => function () {
                        $today = Carbon::now()->format('Y-m-d');
                
                        $transaksiHariIni = Transaksi::whereDate('waktu_transaksi', $today)->first();
                
                        return $transaksiHariIni?->cut_off_olibs;
                    }),
                
                TextInput::make('kewajiban_olibs')
                    ->label('Kewajiban')
                    ->numeric()
                    ->required()
                    ->readOnly()
                    ->prefix('Rp')
                    ->reactive(),

                TextInput::make('outgoing_ossw')
                    ->label('Outgoing OSSW')
                    ->numeric()
                    ->nullable()
                    ->live(debounce: 500)
                    ->prefix('Rp')
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $saldoAkhir = $get('saldo_akhir_hari_berjalan_olibs');
                        $cutOff = $get('cut_off_olibs');
                        $outgoing = $get('outgoing_ossw');
                    
                        if (is_numeric($saldoAkhir) && is_numeric($cutOff) && is_numeric($outgoing)) {
                            $kewajiban = $saldoAkhir - $cutOff;
                            $selisih = $kewajiban - $outgoing;
                            $set('kewajiban_olibs', $kewajiban);
                            $set('selisih', $selisih);
                        }
                    }),

                TextInput::make('incoming_ossw')
                    ->label('Incoming OSSW')
                    ->numeric()
                    ->nullable()
                    ->live(debounce: 500)
                    ->prefix('Rp'),

                TextInput::make('selisih')
                    ->label('Selisih')
                    ->numeric()
                    ->nullable()
                    ->readOnly()
                    ->prefix('Rp')
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $selisih = $get('selisih');
        
                        if (is_numeric($selisih)) {
                            $status = $selisih < 10000000 ? 'warning' : 'good';
                            $set('status', $status);
                        }
                    }),

                TextInput::make('status')
                    ->reactive()
                    ->hidden(),

                DateTimePicker::make('waktu_transaksi')
                    ->label('Waktu Transaksi')
                    ->required()
                    ->seconds(false) // kalau tidak perlu detik
                    ->displayFormat('d M Y H:i') // tampilannya bisa kamu atur
                    ->native() // opsional, biar pakai flatpickr
                    ->default($formattedTime), // setel default value dengan waktu yang sudah ditambah 30 menit

                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('saldo_awal_hari_olibs')
                    ->label('Saldo Awal Hari OLIBS')
                    ->money('idr')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('saldo_akhir_hari_berjalan_olibs')
                    ->label('Saldo Akhir Hari Berjalan OLIBS')
                    ->money('idr')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('cut_off_olibs')
                    ->label('Cut Off')
                    ->money('idr')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('kewajiban_olibs')
                    ->label('Kewajiban')
                    ->money('idr')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('outgoing_ossw')
                    ->label('Outgoing OSSW')
                    ->money('idr')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('incoming_ossw')
                    ->label('Incoming OSSW')
                    ->money('idr')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('selisih')
                    ->label('Selisih')
                    ->money('idr')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'warning' => 'warning',
                        'good' => 'success',
                    }),

                TextColumn::make('waktu_transaksi')
                    ->label('Waktu Transaksi')
                    ->sortable()
                    ->searchable()
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'warning' => 'Warning',
                        'good' => 'Good',
                    ]),
                Filter::make('waktu_transaksi')
                    ->form([
                        DatePicker::make('Dari'),
                        DatePicker::make('Sampai')
                            ->minDate(fn (Get $get) => $get('Dari')), // Set minimum date to the value of 'Dari'
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['Dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('waktu_transaksi', '>=', $date),
                            )
                            ->when(
                                $data['Sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('waktu_transaksi', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
            ])
            ->headerActions([
                Action::make('Download Report')
                    ->label('Download Report')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->form([
                        DatePicker::make('tanggal')
                            ->label('Pilih Tanggal')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $tanggal = $data['tanggal'];
                        $fileName = 'transaksi_' . Carbon::parse($tanggal)->format('d/m/Y') . '.xlsx';
    
                        return Excel::download(new TransaksiExport($tanggal), $fileName);
                    }),
            ]);
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make(),
            //     ]),
            // ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksis::route('/'),
            'create' => Pages\CreateTransaksi::route('/create'),
            'edit' => Pages\EditTransaksi::route('/{record}/edit'),
        ];
    }
}
