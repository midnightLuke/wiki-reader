<?php

namespace MidnightLuke\WikiReader\Tree\Node;

use Tree\Node\Node;

class MenuNode extends Node
{
    private $path;
    private $title;
    private $active;
    private $document;

    public function __construct()
    {
        parent::__construct();
        $this->active = false;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function isActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function setDocument(\SplFileInfo $document = null)
    {
        $this->document = $document;
    }

    public function isLinked()
    {
        return (bool) isset($this->document);
    }
}
