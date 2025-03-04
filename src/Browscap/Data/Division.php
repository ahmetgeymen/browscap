<?php

declare(strict_types=1);

namespace Browscap\Data;

/**
 * Represents a useragent division as defined in the resources/user-agents directory
 *
 * @phpstan-import-type UserAgentData from UserAgent
 * @phpstan-type DivisionData array{division: string, versions?: array<int, int|string>, sortIndex: positive-int, lite: bool, standard: bool, userAgents: array<int, UserAgentData>}
 */
class Division
{
    private string $name = '';

    private string $fileName = '';

    private int $sortIndex = 0;

    private bool $lite = false;

    private bool $standard = false;

    /** @var array<int, int|string> */
    private array $versions = [];

    /** @var UserAgent[] */
    private array $userAgents = [];

    /**
     * @param UserAgent[]            $userAgents
     * @param array<int, int|string> $versions
     *
     * @throws void
     */
    public function __construct(
        string $name,
        int $sortIndex,
        array $userAgents,
        bool $lite,
        bool $standard,
        array $versions,
        string $fileName
    ) {
        $this->name       = $name;
        $this->sortIndex  = $sortIndex;
        $this->userAgents = $userAgents;
        $this->lite       = $lite;
        $this->standard   = $standard;
        $this->versions   = $versions;
        $this->fileName   = $fileName;
    }

    /**
     * @throws void
     */
    public function isLite(): bool
    {
        return $this->lite;
    }

    /**
     * @throws void
     */
    public function isStandard(): bool
    {
        return $this->standard;
    }

    /**
     * @throws void
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws void
     */
    public function getSortIndex(): int
    {
        return $this->sortIndex;
    }

    /**
     * @return array<UserAgent>
     *
     * @throws void
     */
    public function getUserAgents(): array
    {
        return $this->userAgents;
    }

    /**
     * @return array<int, int|string>
     *
     * @throws void
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    /**
     * @throws void
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }
}
