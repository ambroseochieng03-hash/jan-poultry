<?php

require_once
__DIR__ .
'/../models/Dashboard.php';

class DashboardController
{
    private Dashboard $dashboard;

    public function __construct()
    {
        $this->dashboard =
        new Dashboard();
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function getOrdersCount(
        ?int $userId,
        ?string $guestId
    ): int {

        return
        $this->dashboard
        ->getOrdersCount(
            $userId,
            $guestId
        );
    }

    public function getCartItemsCount(
        ?int $userId,
        ?string $guestId
    ): int {

        return
        $this->dashboard
        ->getCartItemsCount(
            $userId,
            $guestId
        );
    }

    public function getNotificationsCount(
        ?int $userId,
        ?string $guestId
    ): int {

        return
        $this->dashboard
        ->getNotificationsCount(
            $userId,
            $guestId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function getProductsCount(): int
    {
        return
        $this->dashboard
        ->getProductsCount();
    }

    public function getAdminOrdersCount(): int
    {
        return
        $this->dashboard
        ->getAdminOrdersCount();
    }

    public function getCustomersCount(): int
    {
        return
        $this->dashboard
        ->getCustomersCount();
    }

    public function getAdminsCount(): int
    {
        return
        $this->dashboard
        ->getAdminsCount();
    }

    public function getCompletedDeliveriesCount(): int
    {
        return
        $this->dashboard
        ->getCompletedDeliveriesCount();
    }

    public function getLogsCount(): int
    {
        return
        $this->dashboard
        ->getLogsCount();
    }
}