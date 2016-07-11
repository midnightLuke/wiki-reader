<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Aptoma\Twig\Extension\MarkdownEngine;
use Aptoma\Twig\Extension\MarkdownExtension;
use MidnightLuke\WikiReader\Utility\MenuTreeBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Yaml\Yaml;

// Create our application and get config.
$app = new Silex\Application();
$config = Yaml::parse(file_get_contents(__DIR__ . '/../config/parameters.yml'));

// Determine wiki directory.
$wiki_dir = $config['parameters']['wiki_path'];
$wiki_dir = (substr($wiki_dir, 0, 1) == '/') ? $wiki_dir : __DIR__ . '/../' . $wiki_dir;

// Assets and Twig.
$app->register(new Silex\Provider\AssetServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../templates',
));

// Markdown filter.
$engine = new MarkdownEngine\MichelfMarkdownEngine();
$app['twig']->addExtension(new MarkdownExtension($engine));

// Register the only path, to attempt to load a document.
$app->match('/{document}', function (Request $request, $document) use ($app, $config, $wiki_dir) {
    // Build menu tree and get active leaf.
    $tree = MenuTreeBuilder::fromDirectory($wiki_dir, $request);
    $active = MenuTreeBuilder::getActive($tree);

    // Pass document contents.
    return $app['twig']->render('template.html.twig', [
        'document' => file_get_contents($document),
        'wiki_title' => $config['parameters']['wiki_title'],
        'menu_tree' => $tree,
        'active_leaf' => $active,
    ]);
})
->assert('document', '.+')
->value('document', 'index')
->convert('document', function ($document) use ($wiki_dir) {
    // Determine the file to show.
    if (file_exists($wiki_dir . '/' . $document . '.md')) {
        $file = $wiki_dir . '/' . $document . '.md';
    } elseif (file_exists($wiki_dir . '/' . $document . '.mdown')) {
        $file = $wiki_dir . '/' . $document . '.mdown';
    } elseif (file_exists($wiki_dir . '/' . $document . '/index.md')) {
        $file = $wiki_dir . '/' . $document . '/index.md';
    } elseif (file_exists($wiki_dir . '/' . $document . '/index.mdown')) {
        $file = $wiki_dir . '/' . $document . '/index.mdown';
    } else {
        // Couldn't locate an appropriate file.
        throw new NotFoundHttpException('Document not found.');
    }

    // Return the file.
    return $file;
});

// And we're off.
$app->run();
