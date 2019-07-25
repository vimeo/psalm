<?php
namespace Psalm\SourceControl\Git;

/**
 * Remote info.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class RemoteInfo
{
    /**
     * Remote name.
     *
     * @var null|string
     */
    protected $name;

    /**
     * Remote URL.
     *
     * @var null|string
     */
    protected $url;

    public function toArray() : array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
        ];
    }

    // accessor

    /**
     * Set remote name.
     *
     * @param string $name remote name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Return remote name.
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set remote URL.
     *
     * @param string $url remote URL
     *
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Return remote URL.
     *
     * @return null|string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
