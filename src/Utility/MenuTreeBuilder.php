<?php

namespace MidnightLuke\WikiReader\Utility;

use MidnightLuke\WikiReader\Tree\Node\MenuNode;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

class MenuTreeBuilder
{
    public static function fromDirectory($directory, Request $request = null)
    {
        // Index these so that we can reference them later.
        $index = [];

        // Create the root node.
        $root = new MenuNode();
        $root->setPath('/');
        $index[$root->getPath()] = $root;

        // Scan the directory and set up the rest of the tree.
        $finder = new Finder();
        $finder
            ->in($directory)
            ->notName('index.md')
            ->notName('index.mdown');
        foreach ($finder as $resource) {
            if ($resource->isFile()
                && !preg_match('/^.*(\.md|\.mdown)$/', $resource->getFilename())) {
                // This is NOT a markdown file, run away.
                continue;
            }

            // Create menu node.
            $leaf = new MenuNode();
            $leaf->setPath(self::cleanPath($resource));
            $leaf->setTitle(self::fetchTitle($resource));
            $leaf->setDocument(self::fetchDocument($resource));

            // Set up relations.
            $leaf->setParent($index[self::parentPath($leaf->getPath())]);
            $leaf->getParent()->addChild($leaf);
            $index[$leaf->getPath()] = $leaf;
        }

        // Set active item.
        if (isset($request) && isset($index[urldecode($request->getPathInfo())])) {
            $index[urldecode($request->getPathInfo())]->setActive(true);
        }

        return $root;
    }

    public static function getActive(MenuNode $node)
    {
        if ($node->isActive()) {
            return $node;
        }

        foreach ($node->getChildren() as $leaf) {
            $active = self::getActive($leaf);
            if ($active !== null) {
                return $active;
            }
        }
    }

    public static function cleanPath(\SplFileInfo $resource)
    {
        $path = $resource->getRelativePath();
        $filename = $resource->getFilename();

        // Strip markdown extensions.
        if ($resource->isFile()) {
            $filename = str_replace(['.md', '.mdown'], '', $filename);
        }

        // Prefix pathname and filename.
        $path = (empty($path)) ? '' : '/' . $path;
        $filename = '/' . $filename;

        // Return the cleaned path.
        return $path . $filename;
    }

    public static function parentPath($path)
    {
        $parent = substr($path, 0, strrpos($path, '/'));
        return (empty($parent)) ? '/' : $parent;
    }

    public static function fetchTitle(\SplFileInfo $resource)
    {
        // This is a directory, look for files we can use.
        if ($resource->isDir()) {
            if (file_exists($resource->getRealPath() . '/index.md')) {
                $resource = new \SplFileInfo($resource->getRealPath() . '/index.md');
            } elseif (file_exists($resource->getRealPath() . '/index.mdown')) {
                $resource = new \SplFileInfo($resource->getRealPath() . '/index.mdown');
            } else {
                return $resource->getFilename();
            }
        }

        // We have a file we can scan now.
        foreach ($resource->openFile() as $buffer) {
            // Header.
            $start = substr($buffer, 0, 2);
            if ($start == "# ") {
                return trim($buffer, "# \n\r\t");
            }

            // Last line was header.
            if (isset($last)
                && !empty(trim($buffer))
                && strlen(str_replace('=', '', trim($buffer))) === 0) {
                return trim($last);
            }
            $last = $buffer;
        }

        return $resource->getFilename();
    }

    public static function fetchDocument(\SplFileInfo $resource)
    {
        if ($resource->isFile()) {
            return $resource;
        }

        if (file_exists($resource->getRealPath() . '/index.md')) {
            return new \SplFileInfo($resource->getRealPath() . '/index.md');
        } elseif (file_exists($resource->getRealPath() . '/index.mdown')) {
            return new \SplFileInfo($resource->getRealPath() . '/index.mdown');
        } else {
            return null;
        }
    }
}
