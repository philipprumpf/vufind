<?php

/**
 * Account menu view helper
 *
 * PHP version 8
 *
 * Copyright (C) Moravian library 2024.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace VuFind\View\Helper\Root;

use VuFind\Db\Entity\UserEntityInterface;
use VuFind\ILS\Connection as IlsConnection;

/**
 * Account menu view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class AccountMenu extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Constructor
     *
     * @param array $config AccountMenu configuration
     */
    public function __construct(protected array $config)
    {
    }

    /**
     * Get all groups with items to display.
     *
     * @return array
     */
    public function getMenu(): array
    {
        $menu = $this->config;
        if (!$menu) {
            $menu = $this->getDefaultMenu();
        } elseif ($menu['MenuItems'] ?? false) {
            // backward compatibility for outdated configurations
            $default = $this->getDefaultMenu();
            $default['Account']['MenuItems'] = $menu['MenuItems'];
            $menu = $default;
        }

        $availableGroups = [];
        foreach ($this->filterAvailable($menu) as $name => $group) {
            // skip groups without items to display
            if ($items = $this->filterAvailable($group['MenuItems'])) {
                $group['MenuItems'] = $items;
                $availableGroups[$name] = $group;
            }
        }

        return $availableGroups;
    }

    /**
     * Get available items from a given list.
     *
     * @param array $list Items to filter
     *
     * @return array
     */
    protected function filterAvailable(array $list): array
    {
        return array_filter(
            $list,
            function ($item) {
                return !isset($item['checkMethod']) || $this->{$item['checkMethod']}();
            }
        );
    }

    /**
     * Get default menu items
     *
     * @return array
     */
    protected function getDefaultMenu(): array
    {
        return [
            'Account' => [
                'name' => 'acc',
                'label' => 'Your Account',
                'id' => 'acc-menu-acc-header',
                'class' => 'account-menu',
                'MenuItems' => [
                    [
                        'name' => 'favorites',
                        'label' => 'saved_items',
                        'route' => 'myresearch-favorites',
                        'icon' => 'user-favorites',
                        'checkMethod' => 'checkFavorites',
                    ],
                    [
                        'name' => 'checkedout',
                        'label' => 'Checked Out Items',
                        'route' => 'myresearch-checkedout',
                        'icon' => 'user-checked-out',
                        'status' => true,
                        'checkMethod' => 'checkCheckedout',
                    ],
                    [
                        'name' => 'historicloans',
                        'label' => 'Loan History',
                        'route' => 'checkouts-history',
                        'icon' => 'user-loan-history',
                        'checkMethod' => 'checkHistoricloans',
                    ],
                    [
                        'name' => 'holds',
                        'label' => 'Holds and Recalls',
                        'route' => 'holds-list',
                        'icon' => 'user-holds',
                        'status' => true,
                        'checkMethod' => 'checkHolds',
                    ],
                    [
                        'name' => 'storageRetrievalRequests',
                        'label' => 'Storage Retrieval Requests',
                        'route' => 'myresearch-storageretrievalrequests',
                        'icon' => 'user-storage-retrievals',
                        'status' => true,
                        'checkMethod' => 'checkStorageRetrievalRequests',
                    ],
                    [
                        'name' => 'ILLRequests',
                        'label' => 'Interlibrary Loan Requests',
                        'route' => 'myresearch-illrequests',
                        'icon' => 'user-ill-requests',
                        'status' => true,
                        'checkMethod' => 'checkILLRequests',
                    ],
                    [
                        'name' => 'fines',
                        'label' => 'Fines',
                        'route' => 'myresearch-fines',
                        'status' => true,
                        'checkMethod' => 'checkFines',
                        'iconMethod' => 'finesIcon',
                    ],
                    [
                        'name' => 'profile',
                        'label' => 'Profile',
                        'route' => 'myresearch-profile',
                        'icon' => 'profile',
                    ],
                    [
                        'name' => 'librarycards',
                        'label' => 'Library Cards',
                        'route' => 'librarycards-home',
                        'icon' => 'barcode',
                        'checkMethod' => 'checkLibraryCards',
                    ],
                    [
                        'name' => 'dgcontent',
                        'label' => 'Overdrive Content',
                        'route' => 'overdrive-mycontent',
                        'icon' => 'overdrive',
                        'checkMethod' => 'checkOverdrive',
                    ],
                    [
                        'name' => 'history',
                        'label' => 'Search History',
                        'route' => 'search-history',
                        'icon' => 'search',
                        'checkMethod' => 'checkHistory',
                    ],
                    [
                        'name' => 'logout',
                        'label' => 'Log Out',
                        'route' => 'myresearch-logout',
                        'icon' => 'sign-out',
                        'checkMethod' => 'checkLogout',
                    ],
                ],
            ],
            'Lists' => [
                'label' => 'Your Lists',
                'id' => 'acc-menu-lists-header',
                'checkMethod' => 'checkUserlistMode',
                'MenuItems' => [
                    [
                        'template' => 'myresearch/menu-mylists.phtml',
                        'icon' => 'user-list',
                    ],
                    [
                        'name' => 'newlist',
                        'label' => 'Create a List',
                        'route' => 'editList',
                        'routeParams' => [
                            'id' => 'NEW',
                        ],
                        'icon' => 'ui-add',
                    ],
                ],
            ],
        ];
    }

    /**
     * Check whether to show favorites item
     *
     * @return bool
     */
    public function checkFavorites(): bool
    {
        return $this->getView()->plugin('userlist')->getMode() !== 'disabled';
    }

    /**
     * Check whether to show checkedout item
     *
     * @return bool
     */
    public function checkCheckedout(): bool
    {
        return $this->checkIlsCapability('getMyTransactions');
    }

    /**
     * Check whether to show historicloans item
     *
     * @return bool
     */
    public function checkHistoricloans(): bool
    {
        return $this->checkIlsFunction('getMyTransactionHistory');
    }

    /**
     * Check whether to show holds item
     *
     * @return bool
     */
    public function checkHolds(): bool
    {
        return $this->checkIlsCapability('getMyHolds');
    }

    /**
     * Check whether to show storageRetrievalRequests item
     *
     * @return bool
     */
    public function checkStorageRetrievalRequests(): bool
    {
        return $this->checkIlsFunction('StorageRetrievalRequests');
    }

    /**
     * Check whether to show ILLRequests item
     *
     * @return bool
     */
    public function checkILLRequests(): bool
    {
        return $this->checkIlsFunction('ILLRequests');
    }

    /**
     * Check whether to show fines item
     *
     * @return bool
     */
    public function checkFines(): bool
    {
        return $this->checkIlsCapability('getMyFines');
    }

    /**
     * Check whether to show librarycards item
     *
     * @return bool
     */
    public function checkLibraryCards(): bool
    {
        return $this->isIlsOnline() && $this->getUser()
            && $this->getView()->plugin('accountCapabilities')()->libraryCardsEnabled();
    }

    /**
     * Check whether to show overdrive item
     *
     * @return bool
     */
    public function checkOverdrive(): bool
    {
        return $this->getView()->plugin('overdrive')->showMyContentLink();
    }

    /**
     * Check whether to show searchhistory item
     *
     * @return bool
     */
    public function checkHistory(): bool
    {
        return $this->getView()->plugin('accountCapabilities')()->getSavedSearchSetting() === 'enabled';
    }

    /**
     * Check whether to show logout item
     *
     * @return bool
     */
    public function checkLogout(): bool
    {
        return (bool)$this->getUser();
    }

    /**
     * Check whether to show user lists.
     *
     * @return bool
     */
    public function checkUserlistMode(): bool
    {
        return $this->view->auth()->getUserObject()
            && ($this->view->userlist()->getMode() !== 'disabled');
    }

    /**
     * Check ILS connection capability
     *
     * @param string $capability Name of then ILS method to check
     *
     * @return bool
     */
    protected function checkIlsCapability(string $capability): bool
    {
        return $this->isIlsOnline()
            && $this->getIlsConnection()->checkCapability($capability, $this->getCapabilityParams());
    }

    /**
     * Check ILS function capability
     *
     * @param string $function The name of the ILS function to check.
     *
     * @return bool
     */
    protected function checkIlsFunction(string $function): bool
    {
        return $this->isIlsOnline()
            && $this->getIlsConnection()->checkFunction($function, $this->getCapabilityParams());
    }

    /**
     * Check whether the ILS connection is available
     *
     * @return bool
     */
    protected function isIlsOnline(): bool
    {
        return 'ils-none' !== $this->getIlsConnection()->getOfflineMode();
    }

    /**
     * Get params for checking ILS capability/function
     *
     * @return array
     */
    protected function getCapabilityParams(): array
    {
        $patron = $this->getUser() ? $this->getAuthHelper()->getILSPatron() : false;
        return $patron ? compact('patron') : [];
    }

    /**
     * Create icon name for fines item
     *
     * @return string
     */
    public function finesIcon(): string
    {
        $icon = 'currency-'
            . strtolower($this->getView()->plugin('config')->get('config')->Site->defaultCurrency ?? 'usd');
        return $icon;
    }

    /**
     * Get authentication view helper
     *
     * @return Auth
     */
    protected function getAuthHelper(): Auth
    {
        return $this->getView()->plugin('auth');
    }

    /**
     * Get ILS connection view helper
     *
     * @return IlsConnection
     */
    protected function getIlsConnection(): IlsConnection
    {
        return $this->getView()->plugin('ils')();
    }

    /**
     * Render account menu
     *
     * @param string $activeItem The name of current active item
     * @param string $idPrefix   Element ID prefix
     *
     * @return string
     */
    public function render(string $activeItem, string $idPrefix = ''): string
    {
        $contextHelper = $this->getView()->plugin('context');
        $menu = $this->getMenu();

        return $contextHelper->renderInContext(
            'myresearch/menu.phtml',
            [
                'menu' => $menu,
                'active' => $activeItem,
                'idPrefix' => $idPrefix,
                // set items for backward compatibility, might be removed in future releases
                'items' => $menu['Account']['MenuItems'],
            ]
        );
    }

    /**
     * Get authenticated user
     *
     * @return ?UserEntityInterface Object if user is logged in, null otherwise.
     */
    protected function getUser(): ?UserEntityInterface
    {
        return $this->getAuthHelper()->getUserObject();
    }
}
