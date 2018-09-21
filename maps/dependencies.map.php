<?php

/**
 * Список зависимостей. Можно просто указать абсолютное имя класса,
 * с неймспейсами, перечислив в массиве все зависимости, которые должны
 * быть зарезольвены. В этом случае в указанный класс передастся список
 * аргументов в указанном порядке.
 * 
 * Можно объявить отложенную зависимость при помощи флага `delay`. 
 * В этом случае зависимость будет разрешена уже *после* создания класса,
 * когда конструктор отработает. Класс должен реализовывать интерфейс `IDelayedInjection`
 * для того, чтобы можно было делать так.
 * 
 * Можно объявить ленивое разрешение зависимостей при помощи флага `lazy`.
 * В этом случае класс *не будет* создан: будут созданы лишь его зависимости.
 * Инъекция их может происходить, а может и не происходить, по желанию
 * разработчика. В общем случае инъекция реализуется в классе при помощи
 * функции `inject_dependencies_to`.
 * 
 * Если зависимость ленивая, можно объявить предварительную инициализацию
 * при помощи флага `pre-create`. Такие зависимости создаются сразу, 
 * и сохраняются в специальном массиве, их можно получить через метод контейнера.
 * Обычные ленивые зависимости так получить нельзя, т.к. они не созданы на момент
 * попытки получения доступа к ним.
 */
return [
    // Lazy Dependencies
    // 'Main' => [
    //     'dependencies' => [
    //         'AdminInterface\\SubmenuPage' => 'AdminSubmenuPage',
    //         'AdminInterface\\Assets' => 'Assets',
    //         'AdminInterface\\Notices' => 'AdminNotices',
    //         'AdminInterface\\FundColumns' => 'AdminFundColumns',
    //         'AdminInterface\\Ajax' => 'AdminAjax',
    //         'AdminInterface\\MediaUploaderTabs' => 'MediaUploaderTabs',

    //         'CustomFields\\Creator' => 'CustomFieldsCreator',

    //         'Routing\\AdminRouter' => 'AdminRouter',
    //         'Routing\\RequestHandler' => 'RequestHandler',
    //         'Routing\\UrlRewrite' => 'UrlRewrite',

    //         'Handlers\\Posts\\SavePostHandler' => 'SavePostHandler',
    //         'Handlers\\Meta\\DoMetaBoxesHandler' => 'DoMetaBoxesHandler',
    //         'Handlers\\Query\\Search' => 'QuerySearchHandler',

    //         'Modules\\Shortcodes\\FundsList' => 'ShortcodeFundsList',
    //         'Modules\\Shortcodes\\SearchForm' => 'ShortcodeSearchForm',
    //         'Modules\\Shortcodes\\FundTable' => 'ShortcodeFundTable',
    //     ],

    //     'lazy' => true,
    // ],

    // Normal Dependencies
    // 'Modules\\Shortcodes\\FundsList' => [
    //     'Templates\\Renderer',
    //     'Factories\\FundEntity',
    //     'UserInterface\\Pagination',
    // ],
];