<?php

/**
 * @package Flextype
 *
 * @author Sergey Romanenko <awilum@yandex.ru>
 * @link http://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flextype;

use Flextype\Component\{Arr\Arr, Http\Http, Filesystem\Filesystem, Event\Event, Registry\Registry};
use Symfony\Component\Yaml\Yaml;

class Pages
{
    /**
     * An instance of the Cache class
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Page
     *
     * @var Page
     */
    public static $page;

    /**
     * Protected constructor since this is a static class.
     *
     * @access  protected
     */
    protected function __construct()
    {
        static::init();
    }

    /**
     * Init Pages
     *
     * @access protected
     * @return void
     */
    protected static function init() : void
    {
        // The page is not processed and not sent to the display.
        Event::dispatch('onPageBeforeRender');

        // Add parseContent on content event
        Event::addListener('content', 'Flextype\Pages::parseContent');

        // Get current page
        static::$page = static::getPage(Http::getUriString());

        // Display page for current requested url
        static::renderPage(static::$page);

        // The page has been fully processed and sent to the display.
        Event::dispatch('onPageAfterRender');
    }

    /**
     * Page finder
     *
     * @param string $url
     * @param bool   $url_abs
     */
    public static function finder(string $url = '', bool $url_abs = false) : string
    {
        // If url is empty that its a homepage
        if ($url_abs) {
            if ($url) {
                $file = $url;
            } else {
                $file = PAGES_PATH . '/' . Registry::get('site.pages.main') . '/' . 'page.md';
            }
        } else {
            if ($url) {
                $file = PAGES_PATH . '/' . $url . '/page.md';
            } else {
                $file = PAGES_PATH . '/' . Registry::get('site.pages.main') . '/' . 'page.md';
            }
        }

        // Get 404 page if file not exists
        if (Filesystem::fileExists($file)) {
            $file = $file;
        } else {
            $file = PAGES_PATH . '/404/page.md';
            Http::setResponseStatus(404);
        }

        return $file;
    }

    /**
     * Render page
     */
    public static function renderPage(array $page)
    {
        Themes::template(empty($page['template']) ? 'templates/default' : 'templates/' . $page['template'])
            ->assign('page', $page, true)
            ->display();
    }

    /**
     * Page page file
     */
    public static function parseFile(string $file) : array
    {
        $page = trim(Filesystem::getFileContent($file));
        $page = explode('---', $page, 3);

        $frontmatter = Shortcodes::driver()->process($page[1]);
        $result_page = Yaml::parse($frontmatter);

        // Get page url
        $url = str_replace(PAGES_PATH, Http::getBaseUrl(), $file);
        $url = str_replace('page.md', '', $url);
        $url = str_replace('.md', '', $url);
        $url = str_replace('\\', '/', $url);
        $url = str_replace('///', '/', $url);
        $url = str_replace('//', '/', $url);
        $url = str_replace('http:/', 'http://', $url);
        $url = str_replace('https:/', 'https://', $url);
        $url = str_replace('/'.Registry::get('site.pages.main'), '', $url);
        $url = rtrim($url, '/');
        $result_page['url'] = $url;

        // Get page slug
        $url = str_replace(Http::getBaseUrl(), '', $url);
        $url = ltrim($url, '/');
        $url = rtrim($url, '/');
        $result_page['slug'] = str_replace(Http::getBaseUrl(), '', $url);

        // Set page date
        $result_page['date'] = $result_page['date'] ?? date(Registry::get('site.date_format'), filemtime($file));

        // Set page content
        $result_page['content'] = $page[2];

        // Return page
        return $result_page;
    }


    /**
     * Get page
     */
    public static function getPage(string $url = '', bool $raw = false, bool $url_abs = false)
    {
        $file = static::finder($url, $url_abs);

        if ($raw) {
            $page = trim(Filesystem::getFileContent($file));
            static::$page = $page;
            Event::dispatch('onPageContentRawAfter');
        } else {
            $page = static::parseFile($file);
            static::$page = $page;
            static::$page['content'] = Event::dispatch('content', ['content' => static::$page['content']], true);
            Event::dispatch('onPageContentAfter');
        }

        return static::$page;
    }

    /**
     * Parse Content
     *
     * @param $content Сontent to parse
     * @return string
     */
    public static function parseContent(string $content) : string
    {
        $content = Shortcodes::driver()->process($content);
        $content = Markdown::parse($content);

        return $content;
    }

    /**
     * Get Pages
     */
    public static function getPages(string $url = '', bool $raw = false, string $order_by = 'date', string $order_type = 'DESC', int $offset = null, int $length = null)
    {
        // Pages array where founded pages will stored
        $pages = [];

        // Get pages for $url
        // If $url is empty then we want to have a list of pages for /pages dir.
        if ($url == '') {

            // Get pages list
            $pages_list = Filesystem::getFilesList(PAGES_PATH, 'md');

            // Create pages array from pages list
            foreach ($pages_list as $key => $page) {
                $pages[$key] = static::getPage($page, $raw, true);
            }

        } else {

            // Get pages list
            $pages_list = Filesystem::getFilesList(PAGES_PATH . '/' . $url, 'md');

            // Create pages array from pages list and ignore current requested page
            foreach ($pages_list as $key => $page) {
                if (strpos($page, $url.'/page.md') !== false) {
                    // ignore ...
                } else {
                    $pages[$key] = static::getPage($page, $raw, true);
                }
            }

        }

        // Sort and Slice pages if $raw === false
        if (!$raw) {
            $pages = Arr::sort($pages, $order_by, $order_type);

            if ($offset !== null && $length !== null) {
                $pages = array_slice($pages, $offset, $length);
            }
        }

        // Return pages array
        return $pages;
    }

    /**
     * Return the Pages instance.
     * Create it if it's not already created.
     *
     * @access public
     * @return object
     */
    public static function instance()
    {
        return !isset(self::$instance) and self::$instance = new Pages();
    }
}
