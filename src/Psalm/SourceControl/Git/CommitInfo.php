<?php

declare(strict_types=1);

namespace Psalm\SourceControl\Git;

/**
 * Commit info.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
final class CommitInfo
{
    /**
     * Commit ID.
     */
    private ?string $id = null;

    /**
     * Author name.
     */
    private ?string $author_name = null;

    /**
     * Author email.
     */
    private ?string $author_email = null;

    /**
     * Committer name.
     */
    private ?string $committer_name = null;

    /**
     * Committer email.
     */
    private ?string $committer_email = null;

    /**
     * Commit message.
     */
    private ?string $message = null;

    /**
     * Commit message.
     */
    private ?int $date = null;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'author_name' => $this->author_name,
            'author_email' => $this->author_email,
            'committer_name' => $this->committer_name,
            'committer_email' => $this->committer_email,
            'message' => $this->message,
            'date' => $this->date,
        ];
    }

    // accessor

    /**
     * Set commit ID.
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Return commit ID.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set author name.
     */
    public function setAuthorName(string $author_name): self
    {
        $this->author_name = $author_name;

        return $this;
    }

    /**
     * Return author name.
     */
    public function getAuthorName(): ?string
    {
        return $this->author_name;
    }

    /**
     * Set author email.
     */
    public function setAuthorEmail(string $author_email): self
    {
        $this->author_email = $author_email;

        return $this;
    }

    /**
     * Return author email.
     */
    public function getAuthorEmail(): ?string
    {
        return $this->author_email;
    }

    /**
     * Set committer name.
     */
    public function setCommitterName(string $committer_name): self
    {
        $this->committer_name = $committer_name;

        return $this;
    }

    /**
     * Return committer name.
     */
    public function getCommitterName(): ?string
    {
        return $this->committer_name;
    }

    /**
     * Set committer email.
     */
    public function setCommitterEmail(string $committer_email): self
    {
        $this->committer_email = $committer_email;

        return $this;
    }

    /**
     * Return committer email.
     */
    public function getCommitterEmail(): ?string
    {
        return $this->committer_email;
    }

    /**
     * Set commit message.
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Return commit message.
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set commit date
     */
    public function setDate(int $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Return commit date.
     */
    public function getDate(): ?int
    {
        return $this->date;
    }
}
