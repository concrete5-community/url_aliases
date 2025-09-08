<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @Doctrine\ORM\Mapping\Entity(
 *     repositoryClass="Concrete\Package\UrlAliases\Entity\UrlAliasRepository")
 * )
 * @Doctrine\ORM\Mapping\Table(
 *     name="UrlAliases",
 *     indexes={
 *         @Doctrine\ORM\Mapping\Index(columns={"pathIndexed"})
 *     }
 * )
 */
class UrlAlias implements Target
{
    public const PATH_INDEXED_MAX_LENGTH = 255;

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
     * The normalized path alias (indexed).
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=255, nullable=false, options={"comment":"Normalized path alias (indexed)"})
     *
     * @var string
     */
    protected $pathIndexed;

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
     * The target type (see the Target::TARGETTYPE constants).
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=20, nullable=false, options={"comment":"Target type"})
     *
     * @var string
     */
    protected $targetType;

    /**
     * The target value.
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment":"Target value"})
     *
     * @var string
     */
    protected $targetValue;

    /**
     * The fragment identifier to be appended to page targets.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=255, nullable=false, options={"comment":"Fragment identifier to be appended to page targets"})
     *
     * @var string
     */
    protected $fragmentIdentifier;

    /**
     * Forward querystring parameters?
     *
     * @Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment":"Forward querystring parameters?"})
     *
     * @var bool
     */
    protected $forwardQuerystringParams;

    /**
     * Forward POST requests and received data?
     *
     * @Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment":"Forward POST requests and received data?"})
     *
     * @var bool
     */
    protected $forwardPost;

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

    /**
     * The list of localized targets associated to this alias.
     *
     * @Doctrine\ORM\Mapping\OneToMany(targetEntity="Concrete\Package\UrlAliases\Entity\UrlAlias\LocalizedTarget", mappedBy="urlAlias", cascade={"all"})
     * @Doctrine\ORM\Mapping\OrderBy({"language"="ASC","script"="ASC","territory"="ASC"})
     *
     * @var \Doctrine\Common\Collections\Collection|\Concrete\Package\UrlAliases\Entity\UrlAlias\LocalizedTarget[]
     */
    protected $localizedTargets;

    public function __construct()
    {
        $this->id = null;
        $this->createdOn = new DateTime();
        $this->setPath('');
        $this->querystring = '';
        $this->acceptAdditionalQuerystringParams = true;
        $this->enabled = true;
        $this->targetType = Target::TARGETTYPE_PAGE;
        $this->targetValue = '';
        $this->fragmentIdentifier = '';
        $this->forwardQuerystringParams = false;
        $this->forwardPost = false;
        $this->firstHit = null;
        $this->lastHit = null;
        $this->hitCount = 0;
        $this->localizedTargets = new ArrayCollection();
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
        $this->pathIndexed = mb_substr($value, 0, self::PATH_INDEXED_MAX_LENGTH);

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
     * Get the target type (see the Target::TARGETTYPE constants).
     *
     * @see \Concrete\Package\UrlAliases\Entity\Target::getTargetType()
     */
    public function getTargetType(): string
    {
        return $this->targetType;
    }

    /**
     * Set the target type (see the Target::TARGETTYPE constants).
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
     *
     * @see \Concrete\Package\UrlAliases\Entity\Target::getTargetValue()
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
     * Get the fragment identifier to be appended to page targets.
     *
     * @see \Concrete\Package\UrlAliases\Entity\Target::getFragmentIdentifier()
     */
    public function getFragmentIdentifier(): string
    {
        return $this->fragmentIdentifier;
    }

    /**
     * Set the fragment identifier to be appended to page targets.
     *
     * @return $this
     */
    public function setFragmentIdentifier(string $value): self
    {
        $this->fragmentIdentifier = $value;

        return $this;
    }

    /**
     * Forward querystring parameters?
     *
     * @see \Concrete\Package\UrlAliases\Entity\Target::isForwardQuerystringParams()
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
     * Forward POST requests and received data?
     *
     * @see \Concrete\Package\UrlAliases\Entity\Target::isForwardPost()
     */
    public function isForwardPost(): bool
    {
        return $this->forwardPost;
    }

    /**
     * Forward POST requests and received data?
     *
     * @return $this
     */
    public function setForwardPost(bool $value): self
    {
        $this->forwardPost = $value;

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

    /**
     * Get the list of localized targets associated to this alias.
     *
     * @return \Doctrine\Common\Collections\Collection|\Concrete\Package\UrlAliases\Entity\UrlAlias\LocalizedTarget[]
     */
    public function getLocalizedTargets(): Collection
    {
        return $this->localizedTargets;
    }
}
