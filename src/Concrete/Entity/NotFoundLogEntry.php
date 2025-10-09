<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Entity;

use Concrete\Package\UrlAliases\NotFoundLogService;
use DateTime;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Request;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @Doctrine\ORM\Mapping\Entity(
 *     repositoryClass="Concrete\Package\UrlAliases\Entity\NotFoundLogEntryRepository")
 * )
 * @Doctrine\ORM\Mapping\Table(
 *     name="NotFoundLogEntries",
 *     indexes={
 *         @Doctrine\ORM\Mapping\Index(columns={"method"}),
 *         @Doctrine\ORM\Mapping\Index(columns={"pathIndexed"}),
 *         @Doctrine\ORM\Mapping\Index(columns={"querystringIndexed"}),
 *         @Doctrine\ORM\Mapping\Index(columns={"lastHit"}),
 *         @Doctrine\ORM\Mapping\Index(columns={"hitCount"})
 *     }
 * )
 */
class NotFoundLogEntry implements JsonSerializable
{
    public const PATH_INDEXED_MAX_LENGTH = 255;

    public const QUERYSTRING_INDEXED_MAX_LENGTH = 255;

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
     * The method for the path resulting in a 404.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=255, nullable=false, options={"comment":"Method for the path resulting in a 404"})
     *
     * @var string
     */
    protected $method;

    /**
     * The path resulting in a 404.
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment":"Path resulting in a 404"})
     *
     * @var string
     */
    protected $path;

    /**
     * The path resulting in a 404 (indexed).
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=255, nullable=false, options={"comment":"Path resulting in a 404 (indexed)"})
     *
     * @var string
     */
    protected $pathIndexed;

    /**
     * The querystring of the path resulting in a 404.
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment":"Querystring of the path resulting in a 404"})
     *
     * @var string
     */
    protected $querystring;

    /**
     * The querystring of the path resulting in a 404 (indexed).
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=255, nullable=false, options={"comment":"Querystring of the path resulting in a 404 (indexed)"})
     *
     * @var string
     */
    protected $querystringIndexed;

    /**
     * The date/time of the first hit.
     *
     * @Doctrine\ORM\Mapping\Column(type="datetime", nullable=false, options={"comment":"Date/time of the first hit"})
     *
     * @var \DateTime
     */
    protected $firstHit;

    /**
     * The date/time of the last hit.
     *
     * @Doctrine\ORM\Mapping\Column(type="datetime", nullable=false, options={"comment":"Date/time of the last hit"})
     *
     * @var \DateTime
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

    public function __construct(Request $request, NotFoundLogService $service)
    {
        $this->id = null;
        $this->method = $service->normalizeRequestMethod($request);
        $this->path = $service->normalizeRequestPath($request);
        $this->pathIndexed = mb_substr($this->path, 0, self::PATH_INDEXED_MAX_LENGTH);
        $this->querystring = $service->normalizeQuerystring($request);
        $this->querystringIndexed = mb_substr($this->querystring, 0, self::QUERYSTRING_INDEXED_MAX_LENGTH);
        $this->firstHit = new DateTime();
        $this->lastHit = new DateTime();
        $this->hitCount = 1;
    }

    /**
     * Get the record ID (null if not persisted).
     */
    public function getID(): ?int
    {
        return $this->id;
    }

    /**
     * Get the method for the path resulting in a 404.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the path resulting in a 404.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the path resulting in a 404 (indexed).
     */
    public function getPathIndexed(): string
    {
        return $this->pathIndexed;
    }

    /**
     * Get the querystring of the path resulting in a 404.
     */
    public function getQuerystring(): string
    {
        return $this->querystring;
    }

    /**
     * Get the querystring of the path resulting in a 404 (indexed).
     */
    public function getQuerystringIndexed(): string
    {
        return $this->querystringIndexed;
    }

    /**
     * Get the date/time of the first hit.
     */
    public function getFirstHit(): DateTime
    {
        return $this->firstHit;
    }

    /**
     * Get the date/time of the last hit.
     */
    public function getLastHit(): DateTime
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
     * Count a hit to this entry.
     *
     * @return $this
     */
    public function hit(): self
    {
        $this->lastHit = new DateTime();
        $this->hitCount++;
    }

    public static function normalizeRequestPath(Request $request): string
    {
        return $request->getBaseUrl() . $request->getPathInfo();
    }

    /**
     * {@inheritdoc}
     *
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getID(),
            'method' => $this->getMethod(),
            'path' => $this->getPath(),
            'querystring' => $this->getQuerystring(),
            'firstHit' => $this->getFirstHit()->getTimestamp(),
            'lastHit' => $this->getLastHit()->getTimestamp(),
            'hitCount' => $this->getHitCount(),
        ];
    }
}
