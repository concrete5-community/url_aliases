<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Entity;

use DateTime;
use DateTimeInterface;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @Doctrine\ORM\Mapping\Entity(
 *     repositoryClass="Concrete\Package\UrlAliases\Entity\UrlAliasRepository")
 * )
 * @Doctrine\ORM\Mapping\Table(
 *     name="UrlAliases",
 *     indexes={
 *         @Doctrine\ORM\Mapping\Index(name="mlUrlAliasPath", columns={"path"}, options={"lengths":{255}})
 *     }
 * )
 */
class UrlAlias
{
    public const TARGETTYPE_PAGE = 'page';

    public const TARGETTYPE_FILE = 'file';

    public const TARGETTYPE_EXTERNAL_URL = 'external_url';

    /**
     * The record ID (null if not persisted).
     *
     * @Doctrine\ORM\Mapping\Id
     * @Doctrine\ORM\Mapping\Column(type="integer", options={"unsigned":true, "comment":"Record ID"})
     * @Doctrine\ORM\Mapping\GeneratedValue(strategy="AUTO")
     *
     * @var int|null
     */
    protected $id;

    /**
     * The record creation date/time.
     *
     * @Doctrine\ORM\Mapping\Column(type="datetime", nullable=false, options={"comment":"Record creation date/time"})
     *
     * @var \DateTime
     */
    protected $createdOn;

    /**
     * The normalized path alias.
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment":"Normalized path alias"})
     *
     * @var string
     */
    protected $path;

    /**
     * The querystring (without leading question mark).
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment":"Querystring (without leading question mark)"})
     *
     * @var string
     */
    protected $querystring;

    /**
     * Should we accept additional querystring parameters?
     *
     * @Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment":"Should we accept additional querystring parameters?"})
     *
     * @var bool
     */
    protected $acceptAdditionalQuerystringParams;

    /**
     * Is this record enabled?
     *
     * @Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment":"Is this record enabled?"})
     *
     * @var bool
     */
    protected $enabled;

    /**
     * The target type (see the TARGETTYPE constants).
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=20, nullable=false, options={"comment":"Target type"})
     */
    protected $targetType;

    /**
     * The target value.
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment":"Target value"})
     */
    protected $targetValue;

    /**
     * Forward querystring parameters?
     *
     * @Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment":"Forward querystring parameters?"})
     *
     * @var bool
     */
    protected $forwardQuerystringParams;

    /**
     * The date/time of the first hit.
     *
     * @Doctrine\ORM\Mapping\Column(type="datetime", nullable=true, options={"comment":"Date/time of the first hit"})
     *
     * @var \DateTime|null
     */
    protected $firstHit;

    /**
     * The date/time of the last hit.
     *
     * @Doctrine\ORM\Mapping\Column(type="datetime", nullable=true, options={"comment":"Date/time of the last hit"})
     *
     * @var \DateTime|null
     */
    protected $lastHit;

    /**
     * The number of hits.
     *
     * @Doctrine\ORM\Mapping\Column(type="integer", nullable=false, options={"unsigned":true, "comment":"Number of hits"})
     *
     * @var int
     */
    protected $hitCount;

    public function __construct()
    {
        $this->id = null;
        $this->createdOn = new DateTime();
        $this->path = '';
        $this->querystring = '';
        $this->acceptAdditionalQuerystringParams = true;
        $this->enabled = true;
        $this->targetType = self::TARGETTYPE_PAGE;
        $this->targetValue = '';
        $this->forwardQuerystringParams = false;
        $this->firstHit = null;
        $this->lastHit = null;
        $this->hitCount = 0;
    }

    /**
     * Get the record ID (null if not persisted).
     */
    public function getID(): ?int
    {
        return $this->id;
    }

    /**
     * Get The record creation date/time.
     */
    public function getCreatedOn(): DateTimeInterface
    {
        return $this->createdOn;
    }

    /**
     * Get the normalized path alias.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the normalized path alias.
     *
     * @return $this
     */
    public function setPath(string $value): self
    {
        $this->path = $value;

        return $this;
    }

    /**
     * Get the querystring (without leading question mark).
     */
    public function getQuerystring(): string
    {
        return $this->querystring;
    }

    /**
     * Set the querystring (without leading question mark).
     *
     * @return $this
     */
    public function setQuerystring(string $value): self
    {
        $this->querystring = $value;

        return $this;
    }

    public function getPathAndQuerystring(): string
    {
        $result = $this->getPath();
        if (($qs = $this->getQuerystring()) !== '') {
            $result .= '?' . $qs;
        }

        return $result;
    }

    /**
     * Should we accept additional querystring parameters?
     */
    public function isAcceptAdditionalQuerystringParams(): bool
    {
        return $this->acceptAdditionalQuerystringParams;
    }

    /**
     * Should we accept additional querystring parameters?
     *
     * @return $this
     */
    public function setAcceptAdditionalQuerystringParams(bool $value): self
    {
        $this->acceptAdditionalQuerystringParams = $value;

        return $this;
    }

    /**
     * Is this record enabled?
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Is this record enabled?
     *
     * @return $this
     */
    public function setEnabled(bool $value): self
    {
        $this->enabled = $value;

        return $this;
    }

    /**
     * Get the target type (see the TARGETTYPE constants).
     */
    public function getTargetType(): string
    {
        return $this->targetType;
    }

    /**
     * Set the target type (see the TARGETTYPE constants).
     *
     * @return $this
     */
    public function setTargetType(string $value): self
    {
        $this->targetType = $value;

        return $this;
    }

    /**
     * Get the target value.
     */
    public function getTargetValue(): string
    {
        return $this->targetValue;
    }

    /**
     * Set the target value.
     *
     * @return $this
     */
    public function setTargetValue(string $value): self
    {
        $this->targetValue = $value;

        return $this;
    }

    /**
     * Forward querystring parameters?
     */
    public function isForwardQuerystringParams(): bool
    {
        return $this->forwardQuerystringParams;
    }

    /**
     * Forward querystring parameters?
     *
     * @return $this
     */
    public function setForwardQuerystringParams(bool $value): self
    {
        $this->forwardQuerystringParams = $value;

        return $this;
    }

    /**
     * Get the date/time of the first hit.
     */
    public function getFirstHit(): ?DateTimeInterface
    {
        return $this->firstHit;
    }

    /**
     * Get the date/time of the last hit.
     */
    public function getLastHit(): ?DateTimeInterface
    {
        return $this->lastHit;
    }

    /**
     * Get the number of hits.
     */
    public function getHitCount(): int
    {
        return $this->hitCount;
    }

    /**
     * Hit (record a visit) for this alias.
     *
     * @return $this
     */
    public function hit(): self
    {
        $this->lastHit = new DateTime();
        if ($this->firstHit === null) {
            $this->firstHit = $this->lastHit;
        }
        $this->hitCount++;

        return $this;
    }
}
