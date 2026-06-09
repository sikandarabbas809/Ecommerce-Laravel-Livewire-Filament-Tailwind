<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Number;

use function Laravel\Prompts\select;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Order Information')->schema([
                        Select::make('user_id')
                        ->label('Customer')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    
                        Select::make('payment_method')
                        ->options([
                            'stripe' => 'Stripe',
                            'cod' => 'Cash on Delivery'
                        ])
                        ->required(),

                        Select::make('payment_status')
                        ->options([
                            'pending' => 'Pending',
                            'paid' => 'Paid',
                            'fail' => 'Fail',
                        ])
                        ->default('pending')
                        ->required(),

                        ToggleButtons::make('status')
                        ->options([
                            'new' => 'New',
                            'processing' => 'Processing',
                            'shipped' => 'shipped',
                            'delivered' => 'Delivered',
                            'canceled' => 'Canceled'
                        ])
                        ->inline()
                        ->default('new')
                        ->required()
                        ->colors([
                            'new' => 'info',
                            'processing' => 'warning',
                            'shipped' => 'info',
                            'delivered' => 'success',
                            'canceled' => 'danger'
                        ])
                        ->icons([
                            'new' => 'heroicon-m-sparkles',
                            'processing' => 'heroicon-m-arrow-path',
                            'shipped' => 'heroicon-m-truck',
                            'delivered' => 'heroicon-m-check-badge',
                            'canceled' => 'heroicon-m-x-circle'
                        ]),

                        Select::make('currency')
                        ->options([
                            'gbp' => 'GBP',
                            'usd' => 'USD',
                            'pkr' => 'PKR' 
                        ])
                        ->required()
                        ->default('gbp'),

                        Select::make('shipped_method')
                        ->options([
                            'fdex' => 'FDEX',
                            'tcs' => 'TCS',
                            'dhl' => 'DHL'
                        ]),

                        Textarea::make('notes')
                        ->columnSpanFull()
                    ])->columns(2),

                    Section::make('Order Item')->schema([
                        Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->columnSpan(4)
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Set $set) => $set('unit_amount', 
                            Product::find($state)?->price ?? 0))
                            ->afterStateUpdated(fn ($state, Set $set) => $set('total_amount', 
                            Product::find($state)?->price ?? 0)),

                            TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->columnSpan(2)
                            ->reactive()
                            ->afterStateUpdated(fn($state, Set $set, Get $get ) => $set('total_amount',
                            $state * $get('unit_amount'))),
                            
                            TextInput::make('unit_amount')
                            ->required()
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(3),

                            TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->dehydrated()
                            ->columnSpan(3)
                        ])->columns(12),

                        Placeholder::make('grand_total_placeholder')
                        ->label('Grand Total')
                        ->content(function(Get $get, Set $set){
                            $total = 0;
                            if(!$repeaters = $get('items')){
                                return $total;
                            }
                            foreach($repeaters as $key => $repeater){
                                $total += $get("items.{$key}.total_amount");
                            }
                            $set('grand_total', $total);
                            return Number::currency($total, 'GBP');
                        }),
                        Hidden::make('grand_total')
                        ->default(0)
                    ])
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
