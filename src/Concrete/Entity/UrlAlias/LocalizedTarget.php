<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Entity\UrlAlias;

use Concrete\Package\UrlAliases\Entity\Target;
use Concrete\Package\UrlAliases\Entity\UrlAlias;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @Doctrine\ORM\Mapping\Entity(
 * )
 * @Doctrine\ORM\Mapping\Table(
 *     name="UrlAliasLocalizedTargets",
 *     uniqueConstraints={
 *         @Doctrine\ORM\Mapping\UniqueConstraint(name="IX_LocalizedTarget_locale", columns={"urlAlias", "language", "script", "territory"})
 *     }
 * )
 */
class LocalizedTarget implements Target
{
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
     * The URL alias owning of this localized target.
     *
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="Concrete\Package\UrlAliases\Entity\UrlAlias", inversedBy="localizedTargets")
     * @Doctrine\ORM\Mapping\JoinColumn(name="urlAlias", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \Concrete\Package\UrlAliases\Entity\UrlAlias
     */
    protected $urlAlias;

    /**
     * The language.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=3, nullable=false, options={"comment":"Language"})
     *
     * @var string
     *
     * @example 'en'
     */
    protected $language;

    /**
     * The script (empty: any).
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=4, nullable=false, options={"comment":"Script (empty: any)"})
     *
     * @var string
     *
     * @example 'Latn'
     */
    protected $script;

    /**
     * The territory (empty: any).
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=3, nullable=false, options={"comment":"Territory (empty: any)"})
     *
     * @var string
     *
     * @example 'US'
     */
    protected $territory;

    /**
     * The target type (see the Target::TARGETTYPE constants).
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
     * The fragment identifier to be appended to page targets.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=255, nullable=false, options={"comment":"Fragment identifier to be appended to page targets"})
     *
     * @var string
     */
    protected $fragmentIdentifier;

    public function __construct(UrlAlias $urlAlias)
    {
        $this->id = null;
        $this->urlAlias = $urlAlias;
        $this->language = '';
        $this->script = '*';
        $this->territory = '*';
        $this->targetType = Target::TARGETTYPE_PAGE;
        $this->targetValue = '';
        $this->fragmentIdentifier = '';
    }

    /**
     * Get the record ID (null if not persisted).
     */
    public function getID(): ?int
    {
        return $this->id;
    }

    /**
     * Get the URL alias owning of this localized target.
     */
    public function getUrlAlias(): UrlAlias
    {
        return $this->urlAlias;
    }

    /**
     * Get the language.
     *
     * @example 'en'
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set the language.
     *
     * @return $this
     *
     * @example 'en'
     */
    public function setLanguage(string $value): self
    {
        $this->language = $value;

        return $this;
    }

    /**
     * Get the script (empty: any).
     *
     * @example 'Latn'
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * Set the script (empty: any).
     *
     * @return $this
     *
     * @example 'Latn'
     */
    public function setScript(string $value): self
    {
        $this->script = $value;

        return $this;
    }

    /**
     * Get the territory (empty: any).
     *
     * @example 'US'
     */
    public function getTerritory(): string
    {
        return $this->territory;
    }

    /**
     * Set the territory (empty: any).
     *
     * @return $this
     *
     * @example 'US'
     */
    public function setTerritory(string $value): self
    {
        $this->territory = $value;

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
     * Set the fragment to be appended to page targets.
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
        return $this->getUrlAlias()->isForwardQuerystringParams();
    }

    /**
     * Forward POST requests and received data?
     *
     * @see \Concrete\Package\UrlAliases\Entity\Target::isForwardPost()
     */
    public function isForwardPost(): bool
    {
        return $this->getUrlAlias()->isForwardPost();
    }
}
