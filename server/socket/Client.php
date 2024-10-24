<?php

namespace socket;

use Ratchet\ConnectionInterface;

class Client
{
    private int $user_id;
    private ConnectionInterface $socketClient;
    private bool $authorized;
    public int $uniqueId;

    public function __construct(ConnectionInterface $socketClient, int $uniqueId, int $user_id=0, $authorized=false) {
        $this->socketClient = $socketClient;
        $this->uniqueId = $uniqueId;
        $this->user_id = $user_id;
        $this->authorized = $authorized;
    }

    public function isAuthorized(): bool {
        return $this->authorized;
    }
    public function setAuthorized(bool $authorized): void {
        $this->authorized = $authorized;
    }
    public function getWalletId(): int {
        return $this->user_id;
    }
    public function setWalletId(int $walletId): void {
        $this->user_id = $walletId;
    }
    public function getSocketClient(): ConnectionInterface {
        return $this->socketClient;
    }
}