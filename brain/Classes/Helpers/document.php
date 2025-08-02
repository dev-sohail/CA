<?php
/****/

    /**
    * Class Document
    * 
    * Manages HTML document metadata and assets such as title, description, keywords,
    * as well as collections of links, stylesheets, and scripts.
    * 
    * Usage:
    *  - Set page metadata using setTitle(), setDescription(), and setKeywords().
    *  - Add HTML <link> tags (e.g., canonical, alternate) via addLink().
    *  - Add CSS stylesheets via addStyle().
    *  - Add JavaScript files or inline scripts via addScript().
    *  - Retrieve these values using corresponding get*() methods for rendering in views/templates.
    * 
    * This class helps centralize control over common HTML <head> elements.
    */
/****/
class Document
{
    /** @var string Page title */
    private string $title = '';

    /** @var string Meta description content */
    private string $description = '';

    /** @var string Meta keywords content */
    private string $keywords = '';

    /** @var array List of <link> elements with href and rel attributes, keyed by md5 hash */
    private array $links = [];

    /** @var array List of stylesheet <link> elements with href, rel, and media attributes, keyed by md5 hash */
    private array $styles = [];

    /** @var array List of script URLs or inline scripts, keyed by md5 hash */
    private array $scripts = [];

    /**
     * Set the document title.
     *
     * @param string $title The page title
     * @return void
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get the document title.
     *
     * @return string Current page title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the meta description content.
     *
     * @param string $description Meta description
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get the meta description content.
     *
     * @return string Meta description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set the meta keywords content.
     *
     * @param string $keywords Meta keywords (comma separated)
     * @return void
     */
    public function setKeywords(string $keywords): void
    {
        $this->keywords = $keywords;
    }

    /**
     * Get the meta keywords content.
     *
     * @return string Meta keywords
     */
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * Add a link element (typically for canonical, alternate, or preload).
     *
     * @param string $href URL of the linked resource
     * @param string $rel  Relationship attribute (default 'stylesheet')
     * @return void
     */
    public function addLink(string $href, string $rel = 'stylesheet'): void
    {
        // Use hash to prevent duplicates
        $this->links[md5($href . $rel)] = [
            'href' => $href,
            'rel'  => $rel
        ];
    }

    /**
     * Get all added link elements.
     *
     * @return array List of links with keys: href, rel
     */
    public function getLinks(): array
    {
        return array_values($this->links);
    }

    /**
     * Add a stylesheet link element.
     *
     * @param string $href  URL of the CSS file
     * @param string $rel   Relationship attribute (default 'stylesheet')
     * @param string $media Media attribute (default 'screen')
     * @return void
     */
    public function addStyle(string $href, string $rel = 'stylesheet', string $media = 'screen'): void
    {
        $this->styles[md5($href)] = [
            'href'  => $href,
            'rel'   => $rel,
            'media' => $media
        ];
    }

    /**
     * Get all added stylesheets.
     *
     * @return array List of styles with keys: href, rel, media
     */
    public function getStyles(): array
    {
        return array_values($this->styles);
    }

    /**
     * Add a JavaScript file or inline script.
     *
     * @param string $script URL or inline JavaScript code
     * @return void
     */
    public function addScript(string $script): void
    {
        $this->scripts[md5($script)] = $script;
    }

    /**
     * Get all added scripts.
     *
     * @return array List of scripts (URLs or inline code)
     */
    public function getScripts(): array
    {
        return array_values($this->scripts);
    }
}
