<?php

use WireUi\Enum\Packs;
use WireUi\{Components, WireUiConfig as Config};

return [

    /*
    |--------------------------------------------------------------------------
    | Prefix
    |--------------------------------------------------------------------------
    |
    | This option controls the prefix for WireUI components. Examples:
    |
    | 'wireui-' => 'x-wireui-button'
    | 'wireui:' => 'x-wireui:button'
    |
     */

    'prefix' => null,

    /*
    |--------------------------------------------------------------------------
    | Global Styles
    |--------------------------------------------------------------------------
    |
    | This option controls the global styles for WireUI components.
    |
     */

    'style' => [
        'shadow'  => Packs\Shadow::BASE,
        'rounded' => Packs\Rounded::MD,
        'color'   => Packs\Color::PRIMARY,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Configuration
    |--------------------------------------------------------------------------
    |
    | This option controls the default configuration for WireUI components.
    |
     */

    'alert' => Config::alert(),

    'avatar' => Config::avatar(),

    'badge' => Config::badge(),

    'mini-badge' => Config::miniBadge(),

    'button' => Config::button(),

    'mini-button' => Config::miniButton(),

    'card' => Config::card(),

    'checkbox' => Config::checkbox(),

    'color-picker' => Config::wrapper(),

    'datetime-picker' => Config::dateTimePicker(),

    'dialog' => Config::dialog(),

    'dropdown' => Config::dropdown(),

    'icon' => Config::icon(),

    'input' => Config::wrapper(),

    'currency' => Config::wrapper(),

    'maskable' => Config::wrapper(),

    'number' => Config::wrapper(),

    'password' => Config::wrapper(),

    'phone' => Config::wrapper(),

    'link' => Config::link(),

    'modal' => Config::modal(),

    'modal-card' => Config::modal(),

    'native-select' => Config::wrapper(),

    'notifications' => Config::notifications(),

    'radio' => Config::radio(),

    'select' => Config::wrapper(),

    'textarea' => Config::wrapper(),

    'time-picker' => Config::timePicker(),

    'time-selector' => Config::timeSelector(),

    'toggle' => Config::toggle(),

    /*
    |--------------------------------------------------------------------------
    | WireUI Components
    |--------------------------------------------------------------------------
    |
    | Change the alias to call the component with a different name.
    | Extend the component and replace your changes in this file.
    |
     */

    'components' => Config::defaultComponents([

        'alert' => ['alias' => 'form.alert'],

        'avatar' => ['alias' => 'form.avatar'],

        'badge' => ['alias' => 'form.badge'],

        'mini-badge' => ['alias' => 'form.mini.badge'],

        'button' => ['alias' => 'form.button'],

        'mini-button' => ['alias' => 'form.mini.button'],

        'card' => ['alias' => 'form.card'],

        'checkbox' => ['alias' => 'form.checkbox'],

        'color-picker' => ['alias' => 'form.color.picker'],

        'datetime-picker' => ['alias' => 'form.datetime.picker'],

        'dialog' => ['alias' => 'form.dialog'],

        'dropdown' => ['alias' => 'form.dropdown'],

        'icon' => ['alias' => 'form.icon'],

        'input' => ['alias' => 'form.input'],

        'currency' => ['alias' => 'form.currency'],

        'maskable' => ['alias' => 'form.maskable'],

        'number' => ['alias' => 'form.number'],

        'password' => ['alias' => 'form.password'],

        'phone' => ['alias' => 'form.phone'],

        'link' => ['alias' => 'form.link'],

        'modal' => ['alias' => 'form.modal'],

        'modal-card' => ['alias' => 'form.modal.card'],

        'native-select' => ['alias' => 'form.native.select'],

        'notifications' => ['alias' => 'form.notifications'],

        'radio' => ['alias' => 'form.radio'],

        'select' => ['alias' => 'form.select'],

        'textarea' => ['alias' => 'form.textarea'],

        'time-picker' => ['alias' => 'form.time.picker'],

        'time-selector' => ['alias' => 'form.time.selector'],

        'toggle' => ['alias' => 'form.toggle'],

        // 'button' => [
        //     'alias' => 'new-button',
        // ],
        // 'mini-button' => [
        //     'class' => Components\Button\Mini::class,
        //     'alias' => 'new-mini-button',
        // ],
    ]),
];
