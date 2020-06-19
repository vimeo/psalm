<?php

// Examples: Query builder, file, socket.

interface ClosedFile
{
    public function open();
}

interface OpenFile
{
    public function read();
    public function close();
}

interface CannotOpen {}

class MyFile implements ClosedFile, OpenFile, CannotOpen
{
    /**
     * @self-out ClosedFile
     */
    public function __construct() {
    }
    /**
     * @self-out OpenFile|CannotOpen
     */
    public function open() {
        // if can't open, throw exception and invalidate $this
    }
    /**
     * @self-out ClosedFile
     */
    public function close() {
    }
    /**
     * @return string
     */
    public function read() {
        return 'content';
    }
}

$file = new MyFile("somefile");
$file->open();
if ($file instanceof OpenFile) {
    $content = $file->read();
    $file->close();
} elseif ($file instanceof CannotOpen) {
    // Mooo.
}

for ($i = 1; $i < 10; ++$i) {
    // TODO: What here?
    $file->open();
}

// ClosedFile
$file = new MyFile("somefile");
if ($file instanceof OpenFile) {
    // TODO: Impossible
}
if (hardcomputation()) {
    $file->open();
}
// $file is OpenFile|ClosedFile|CannotOpen
// TODO: Have to check state?

/**
 * @param OpenFile $file
 */
function readall(OpenFile $file) {
    $file->read();
    $file->close(); // TODO: Not allowed to change state of aliased variable.
    return $file; // TODO: Not allowed to transfer ownership
}

/**
 * @param string $name
 * @return ClosedFile
 */
function makefile($name) {
    // ClosedFile
    $file = new File($name);
    dosomething($file);  // Can't change state, still closed
    return $file;  // Owner can change
}

/**
 * @param ClosedFile $file
 * @param-out OpenFile $file
 */
function open(ClosedFile &$file)
{
}
