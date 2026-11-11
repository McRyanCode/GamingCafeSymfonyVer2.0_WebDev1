<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    private ?Station $station = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'A user must be assigned to this booking.')]
    private ?User $user = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Start time is required.')]
    private ?\DateTime $startTime = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'End time is required.')]
    private ?\DateTime $endTime = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Gaming tier is required.')]
    private ?string $tier = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Hours duration is required.')]
    #[Assert\Positive(message: 'Hours must be at least 1.')]
    private ?int $hours = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $bookingDate = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Status is required.')]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStation(): ?Station
    {
        return $this->station;
    }

    public function setStation(?Station $station): static
    {
        $this->station = $station;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTime $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getTier(): ?string
    {
        return $this->tier;
    }

    public function setTier(string $tier): static
    {
        $this->tier = $tier;

        return $this;
    }

    public function getHours(): ?int
    {
        return $this->hours;
    }

    public function setHours(int $hours): static
    {
        $this->hours = $hours;

        return $this;
    }

    public function getBookingDate(): ?\DateTime
    {
        return $this->bookingDate;
    }

    public function setBookingDate(?\DateTime $bookingDate): static
    {
        $this->bookingDate = $bookingDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
