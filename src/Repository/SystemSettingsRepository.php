<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SystemSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SystemSettings>
 */
class SystemSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemSettings::class);
    }

    public function findByKey(string $key): ?SystemSettings
    {
        return $this->findOneBy(['settingKey' => $key]);
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->findByKey($key);
        
        if (!$setting) {
            return $default;
        }

        return $setting->getTypedValue();
    }

    public function setValue(string $key, mixed $value, string $type = 'string'): SystemSettings
    {
        $setting = $this->findByKey($key);
        
        if (!$setting) {
            $setting = new SystemSettings($key, (string) $value, $type);
            $this->getEntityManager()->persist($setting);
        } else {
            $setting->setValue((string) $value);
        }
        
        $this->getEntityManager()->flush();
        
        return $setting;
    }

    public function getAllSettings(): array
    {
        return $this->findAll();
    }
}