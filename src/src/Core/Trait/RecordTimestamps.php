<?php

declare(strict_types=1);

namespace App\Core\Trait;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

trait RecordTimestamps
{
    #[ORM\Column(name: 'rec_date_created', type: 'datetime_immutable')]
    private DateTimeImmutable $recDateCreated;

    #[ORM\Column(name: 'rec_date_updated', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $recDateUpdated = null;

    public function recDateCreated(): DateTimeImmutable
    {
        return $this->recDateCreated;
    }

    public function setRecDateCreated(DateTimeImmutable $recDateCreated): self
    {
        $this->recDateCreated = $recDateCreated;

        return $this;
    }

    public function recDateUpdated(): ?DateTimeImmutable
    {
        return $this->recDateUpdated;
    }

    public function setRecDateUpdated(?DateTimeImmutable $recDateUpdated): self
    {
        $this->recDateUpdated = $recDateUpdated;

        return $this;
    }

    public function markAsUpdated(): self
    {
        $this->recDateUpdated = new DateTimeImmutable();

        return $this;
    }

    #[ORM\PrePersist]
    public function initializeRecordTimestamps(): void
    {
        if (isset($this->recDateCreated))
        {
            return;
        }

        $this->recDateCreated = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateRecordTimestamp(): void
    {
        $this->recDateUpdated = new DateTimeImmutable();
    }
}
