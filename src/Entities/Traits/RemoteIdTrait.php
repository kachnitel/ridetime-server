<?php
namespace RideTimeServer\Entities\Traits;

/**
 * Add remote ID and source
 */
trait RemoteIdTrait
{
    /**
     * @Column(type="integer", nullable=true)
     *
     * @var int
     */
    private $remoteId;

    /**
     * @Column(type="string", nullable=true, length=25)
     *
     * @var string
     */
    private $source;

    /**
     * Get the value of remoteId
     *
     * @return int
     */
    public function getRemoteId(): int
    {
        return (int) $this->remoteId;
    }

    /**
     * Set the value of remoteId
     *
     * @param int $remoteId
     *
     * @return self
     */
    public function setRemoteId(int $remoteId)
    {
        $this->remoteId = $remoteId;

        return $this;
    }

    /**
     * Get the value of source
     *
     * @return  string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set the value of source
     *
     * @param  string  $source
     *
     * @return  self
     */
    public function setSource(string $source)
    {
        $this->source = $source;

        return $this;
    }
}
