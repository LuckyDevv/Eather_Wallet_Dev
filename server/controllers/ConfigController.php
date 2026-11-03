<?php
namespace controllers;

class ConfigController
{
    private string $filename;
    private array $config = [];
    const int DETECT = 0;
    const int JSON = 1;
    const int SERIALIZED = 2;
    const int ENUM = 3;
    const int JSON_OPS = JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE;
    const array MYSQL_CONNECTION = [
        'ip' => 'localhost', // Хост БД
        'user' => 'root', // Пользователь БД
        'password' => '', // Пароль от БД
    ];
    private int $type = ConfigController::DETECT;
    private array $formats = [
        "json" => ConfigController::JSON,
        "js" => ConfigController::JSON,
        "sl" => ConfigController::SERIALIZED,
        "serialize" => ConfigController::SERIALIZED
        /* !! Other formats are an instance of the ENUM type !! */
    ];

    public function __construct(string $filename = 'default.txt', $type = ConfigController::DETECT, $default = [])
    {
        $this->init($filename, $type, $default);
    }

    private function init(string $filename, $type, $default): void
    {
        $this->filename = $filename;
        $this->type = $type;
        if ($type == ConfigController::DETECT) {
            $this->type = ConfigController::ENUM;
            if (strpos($filename, '.')) {
                $expansion = trim(strtolower(explode('.', $filename)[1]));
                if (in_array($expansion, $this->formats)) {
                    $this->type = $this->formats[$expansion];
                }
            }
        }
        if (!file_exists($filename)) {
            $this->config = $default;
            $this->save();
        } else {
            $content = file_get_contents($filename);
            switch ($type) {
                case ConfigController::JSON:
                    $this->config = json_decode($content, true);
                    break;
                case ConfigController::SERIALIZED:
                    $this->config = unserialize($content);
                    break;
                case ConfigController::ENUM:
                    $this->config = array_fill_keys($this->parseList($content), true);
                    break;
                default:
                    return;
            }
            if (!is_array($this->config)) {
                $this->config = $default;
            }
        }
    }

    public function save(): bool
    {
        $content = match ($this->type) {
            ConfigController::JSON => json_encode($this->config, ConfigController::JSON_OPS),
            ConfigController::SERIALIZED => serialize($this->config),
            ConfigController::ENUM => implode("\n", array_keys($this->config)),
            default => false,
        };
        return (@file_put_contents($this->filename, $content) !== false);
    }

    public function getFileName(): string
    {
        return $this->filename;
    }

    public function get($key, $default = false)
    {
        return $this->config[$key] ?? $default;
    }

    public function getAll(bool $keys = false): array
    {
        return ($keys ? array_keys($this->config) : $this->config);
    }

    public function set($key, $value = true): void
    {
        $this->config[$key] = $value;
    }

    public function setAll($value): void
    {
        $this->config = $value;
    }

    public function remove($key): void
    {
        if (isset($this->config[$key])) {
            unset($this->config[$key]);
        }
    }

    public function exists($key): bool
    {
        return isset($this->config[$key]);
    }

    private function parseList($content): array
    {
        $result = [];
        foreach (explode("\n", trim(str_replace("\r\n", "\n", $content))) as $v) {
            $v = trim($v);
            if ($v === "") {
                continue;
            }
            $result[] = $v;
            $this->config[$v] = true;
        }
        return $result;
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function __isset($key)
    {
        return $this->exists($key);
    }

    public function __unset($key)
    {
        $this->remove($key);
    }
}
