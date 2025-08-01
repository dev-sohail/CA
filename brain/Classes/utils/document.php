<?php
/**
 * Class Document
 *
 * Manages HTML document metadata and assets such as title, description, keywords,
 * Open Graph tags, Twitter Card meta, canonical URLs, favicons, as well as
 * collections of links, stylesheets, and scripts with advanced features.
 *
 * Usage:
 *  - Set page metadata using setTitle(), setDescription(), and setKeywords().
 *  - Add Open Graph and Twitter Card meta tags for social media sharing.
 *  - Set canonical URL and manage favicons.
 *  - Add HTML <link> tags (e.g., canonical, alternate, preload) via addLink().
 *  - Add CSS stylesheets via addStyle() with media queries and attributes.
 *  - Add JavaScript files or inline scripts via addScript() with async/defer options.
 *  - Add custom meta tags and manage viewport settings.
 *  - Retrieve these values using corresponding get*() methods for rendering in views/templates.
 *  - Generate complete HTML head section with render*() methods.
 *
 * Features:
 *  - Duplicate prevention using content hashing
 *  - Social media meta tags (Open Graph, Twitter Cards)
 *  - Structured data (JSON-LD) support
 *  - Asset prioritization and loading strategies
 *  - Comprehensive HTML head generation
 *
 * This class helps centralize control over all HTML <head> elements and provides
 * a fluent interface for building rich, SEO-friendly web pages.
 */
class Document
{
    /** @var string Page title */
    private string $title = '';

    /** @var string Meta description content */
    private string $description = '';

    /** @var string Meta keywords content */
    private string $keywords = '';

    /** @var string Canonical URL */
    private string $canonical = '';

    /** @var string Viewport meta content */
    private string $viewport = 'width=device-width, initial-scale=1.0';

    /** @var string Document language */
    private string $language = 'en';

    /** @var array Open Graph meta tags */
    private array $openGraph = [];

    /** @var array Twitter Card meta tags */
    private array $twitterCard = [];

    /** @var array Custom meta tags */
    private array $metaTags = [];

    /** @var array List of <link> elements with various attributes, keyed by md5 hash */
    private array $links = [];

    /** @var array List of stylesheet <link> elements with attributes, keyed by md5 hash */
    private array $styles = [];

    /** @var array List of script configurations, keyed by md5 hash */
    private array $scripts = [];

    /** @var array Favicon configurations */
    private array $favicons = [];

    /** @var array JSON-LD structured data */
    private array $structuredData = [];

    /** @var array CSS variables/custom properties */
    private array $cssVariables = [];

    /** @var string Title separator for breadcrumbs */
    private string $titleSeparator = ' | ';

    /** @var string Site name for title suffix */
    private string $siteName = '';

    /**
     * Set the document title.
     *
     * @param string $title The page title
     * @param bool $appendSiteName Whether to append site name
     * @return self For method chaining
     */
    public function setTitle(string $title, bool $appendSiteName = true): self
    {
        $this->title = $title;
        if ($appendSiteName && !empty($this->siteName)) {
            $this->title .= $this->titleSeparator . $this->siteName;
        }
        return $this;
    }

    /**
     * Set the site name for title suffix.
     *
     * @param string $siteName Site name
     * @param string $separator Title separator
     * @return self For method chaining
     */
    public function setSiteName(string $siteName, string $separator = ' | '): self
    {
        $this->siteName = $siteName;
        $this->titleSeparator = $separator;
        return $this;
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
     * @return self For method chaining
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
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
     * @param string|array $keywords Meta keywords (comma separated or array)
     * @return self For method chaining
     */
    public function setKeywords($keywords): self
    {
        if (is_array($keywords)) {
            $this->keywords = implode(', ', $keywords);
        } else {
            $this->keywords = $keywords;
        }
        return $this;
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
     * Set the canonical URL.
     *
     * @param string $url Canonical URL
     * @return self For method chaining
     */
    public function setCanonical(string $url): self
    {
        $this->canonical = $url;
        return $this;
    }

    /**
     * Get the canonical URL.
     *
     * @return string Canonical URL
     */
    public function getCanonical(): string
    {
        return $this->canonical;
    }

    /**
     * Set the viewport meta content.
     *
     * @param string $viewport Viewport content
     * @return self For method chaining
     */
    public function setViewport(string $viewport): self
    {
        $this->viewport = $viewport;
        return $this;
    }

    /**
     * Set the document language.
     *
     * @param string $language Language code (e.g., 'en', 'es', 'fr')
     * @return self For method chaining
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Add Open Graph meta tag.
     *
     * @param string $property OG property name (without 'og:' prefix)
     * @param string $content Property content
     * @return self For method chaining
     */
    public function setOpenGraph(string $property, string $content): self
    {
        $this->openGraph[$property] = $content;
        return $this;
    }

    /**
     * Set multiple Open Graph properties at once.
     *
     * @param array $properties Associative array of OG properties
     * @return self For method chaining
     */
    public function setOpenGraphBulk(array $properties): self
    {
        foreach ($properties as $property => $content) {
            $this->setOpenGraph($property, $content);
        }
        return $this;
    }

    /**
     * Add Twitter Card meta tag.
     *
     * @param string $name Twitter card name (without 'twitter:' prefix)
     * @param string $content Card content
     * @return self For method chaining
     */
    public function setTwitterCard(string $name, string $content): self
    {
        $this->twitterCard[$name] = $content;
        return $this;
    }

    /**
     * Add a custom meta tag.
     *
     * @param string $name Meta tag name or property
     * @param string $content Meta tag content
     * @param string $type Type of meta tag ('name' or 'property')
     * @return self For method chaining
     */
    public function addMetaTag(string $name, string $content, string $type = 'name'): self
    {
        $this->metaTags[md5($name . $type)] = [
            'name' => $name,
            'content' => $content,
            'type' => $type
        ];
        return $this;
    }

    /**
     * Add a link element (canonical, alternate, preload, etc.).
     *
     * @param string $href URL of the linked resource
     * @param string $rel Relationship attribute
     * @param array $attributes Additional attributes
     * @return self For method chaining
     */
    public function addLink(string $href, string $rel = 'stylesheet', array $attributes = []): self
    {
        $linkData = array_merge([
            'href' => $href,
            'rel' => $rel
        ], $attributes);

        $this->links[md5($href . $rel . serialize($attributes))] = $linkData;
        return $this;
    }

    /**
     * Get all added link elements.
     *
     * @return array List of links with their attributes
     */
    public function getLinks(): array
    {
        return array_values($this->links);
    }

    /**
     * Add a stylesheet link element.
     *
     * @param string $href URL of the CSS file
     * @param string $media Media attribute
     * @param array $attributes Additional attributes (integrity, crossorigin, etc.)
     * @return self For method chaining
     */
    public function addStyle(string $href, string $media = 'all', array $attributes = []): self
    {
        $styleData = array_merge([
            'href' => $href,
            'rel' => 'stylesheet',
            'media' => $media
        ], $attributes);

        $this->styles[md5($href)] = $styleData;
        return $this;
    }

    /**
     * Add inline CSS styles.
     *
     * @param string $css CSS content
     * @param array $attributes Additional attributes for style tag
     * @return self For method chaining
     */
    public function addInlineStyle(string $css, array $attributes = []): self
    {
        $styleData = array_merge([
            'content' => $css,
            'inline' => true
        ], $attributes);

        $this->styles[md5($css)] = $styleData;
        return $this;
    }

    /**
     * Get all added stylesheets.
     *
     * @return array List of styles with their attributes
     */
    public function getStyles(): array
    {
        return array_values($this->styles);
    }

    /**
     * Add a JavaScript file or inline script.
     *
     * @param string $script URL or inline JavaScript code
     * @param array $attributes Script attributes (async, defer, type, etc.)
     * @return self For method chaining
     */
    public function addScript(string $script, array $attributes = []): self
    {
        // Determine if it's a URL or inline code
        $isUrl = filter_var($script, FILTER_VALIDATE_URL) || 
                 (strpos($script, '/') !== false && !preg_match('/[;\{\}]/', $script));

        $scriptData = array_merge([
            $isUrl ? 'src' : 'content' => $script,
            'inline' => !$isUrl
        ], $attributes);

        $this->scripts[md5($script)] = $scriptData;
        return $this;
    }

    /**
     * Add an external JavaScript file with loading strategy.
     *
     * @param string $src JavaScript file URL
     * @param string $loading Loading strategy: 'defer', 'async', or 'normal'
     * @param array $attributes Additional attributes
     * @return self For method chaining
     */
    public function addScriptFile(string $src, string $loading = 'defer', array $attributes = []): self
    {
        if ($loading === 'defer') {
            $attributes['defer'] = true;
        } elseif ($loading === 'async') {
            $attributes['async'] = true;
        }

        return $this->addScript($src, $attributes);
    }

    /**
     * Get all added scripts.
     *
     * @return array List of scripts with their configurations
     */
    public function getScripts(): array
    {
        return array_values($this->scripts);
    }

    /**
     * Add favicon configurations.
     *
     * @param string $href Favicon URL
     * @param string $type MIME type
     * @param string $sizes Icon sizes (e.g., '32x32')
     * @return self For method chaining
     */
    public function addFavicon(string $href, string $type = 'image/x-icon', string $sizes = ''): self
    {
        $favicon = [
            'href' => $href,
            'rel' => 'icon',
            'type' => $type
        ];

        if (!empty($sizes)) {
            $favicon['sizes'] = $sizes;
        }

        $this->favicons[md5($href . $sizes)] = $favicon;
        return $this;
    }

    /**
     * Add structured data (JSON-LD).
     *
     * @param array $data Structured data array
     * @param string $type Schema.org type (optional)
     * @return self For method chaining
     */
    public function addStructuredData(array $data, string $type = ''): self
    {
        if (!empty($type) && !isset($data['@type'])) {
            $data['@type'] = $type;
        }

        if (!isset($data['@context'])) {
            $data['@context'] = 'https://schema.org';
        }

        $this->structuredData[] = $data;
        return $this;
    }

    /**
     * Add CSS custom properties/variables.
     *
     * @param string $property CSS property name (without --)
     * @param string $value Property value
     * @return self For method chaining
     */
    public function addCSSVariable(string $property, string $value): self
    {
        $this->cssVariables[$property] = $value;
        return $this;
    }

    /**
     * Render the complete HTML head section.
     *
     * @return string Complete HTML head content
     */
    public function renderHead(): string
    {
        $html = [];

        // Basic meta tags
        if (!empty($this->title)) {
            $html[] = '<title>' . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') . '</title>';
        }

        if (!empty($this->description)) {
            $html[] = '<meta name="description" content="' . htmlspecialchars($this->description, ENT_QUOTES, 'UTF-8') . '">';
        }

        if (!empty($this->keywords)) {
            $html[] = '<meta name="keywords" content="' . htmlspecialchars($this->keywords, ENT_QUOTES, 'UTF-8') . '">';
        }

        // Viewport
        $html[] = '<meta name="viewport" content="' . htmlspecialchars($this->viewport, ENT_QUOTES, 'UTF-8') . '">';

        // Charset
        $html[] = '<meta charset="UTF-8">';

        // Language
        if (!empty($this->language)) {
            $html[] = '<meta http-equiv="Content-Language" content="' . htmlspecialchars($this->language, ENT_QUOTES, 'UTF-8') . '">';
        }

        // Canonical URL
        if (!empty($this->canonical)) {
            $html[] = '<link rel="canonical" href="' . htmlspecialchars($this->canonical, ENT_QUOTES, 'UTF-8') . '">';
        }

        // Custom meta tags
        foreach ($this->metaTags as $meta) {
            $html[] = '<meta ' . $meta['type'] . '="' . htmlspecialchars($meta['name'], ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars($meta['content'], ENT_QUOTES, 'UTF-8') . '">';
        }

        // Open Graph tags
        foreach ($this->openGraph as $property => $content) {
            $html[] = '<meta property="og:' . htmlspecialchars($property, ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">';
        }

        // Twitter Card tags
        foreach ($this->twitterCard as $name => $content) {
            $html[] = '<meta name="twitter:' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">';
        }

        // Favicons
        foreach ($this->favicons as $favicon) {
            $html[] = $this->renderLinkTag($favicon);
        }

        // Links
        foreach ($this->links as $link) {
            $html[] = $this->renderLinkTag($link);
        }

        // Stylesheets
        foreach ($this->styles as $style) {
            if (isset($style['inline']) && $style['inline']) {
                $html[] = '<style' . $this->renderAttributes(array_diff_key($style, ['content' => '', 'inline' => ''])) . '>' . $style['content'] . '</style>';
            } else {
                $html[] = $this->renderLinkTag($style);
            }
        }

        // CSS Variables
        if (!empty($this->cssVariables)) {
            $cssVars = ':root { ';
            foreach ($this->cssVariables as $property => $value) {
                $cssVars .= '--' . $property . ': ' . $value . '; ';
            }
            $cssVars .= '}';
            $html[] = '<style>' . $cssVars . '</style>';
        }

        // Scripts
        foreach ($this->scripts as $script) {
            if (isset($script['inline']) && $script['inline']) {
                $html[] = '<script' . $this->renderAttributes(array_diff_key($script, ['content' => '', 'inline' => ''])) . '>' . $script['content'] . '</script>';
            } else {
                $html[] = '<script' . $this->renderAttributes($script) . '></script>';
            }
        }

        // Structured Data
        foreach ($this->structuredData as $data) {
            $html[] = '<script type="application/ld+json">' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>';
        }

        return implode("\n", $html);
    }

    /**
     * Render a link tag with attributes.
     *
     * @param array $attributes Link attributes
     * @return string HTML link tag
     */
    private function renderLinkTag(array $attributes): string
    {
        return '<link' . $this->renderAttributes($attributes) . '>';
    }

    /**
     * Render HTML attributes from array.
     *
     * @param array $attributes Attribute array
     * @return string Formatted HTML attributes
     */
    private function renderAttributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $html .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            } elseif ($value !== false && $value !== null && $value !== '') {
                $html .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
        return $html;
    }

    /**
     * Reset all document data.
     *
     * @return self For method chaining
     */
    public function reset(): self
    {
        $this->title = '';
        $this->description = '';
        $this->keywords = '';
        $this->canonical = '';
        $this->openGraph = [];
        $this->twitterCard = [];
        $this->metaTags = [];
        $this->links = [];
        $this->styles = [];
        $this->scripts = [];
        $this->favicons = [];
        $this->structuredData = [];
        $this->cssVariables = [];
        return $this;
    }

    /**
     * Get all Open Graph tags.
     *
     * @return array Open Graph tags
     */
    public function getOpenGraph(): array
    {
        return $this->openGraph;
    }

    /**
     * Get all Twitter Card tags.
     *
     * @return array Twitter Card tags
     */
    public function getTwitterCard(): array
    {
        return $this->twitterCard;
    }

    /**
     * Get all structured data.
     *
     * @return array Structured data
     */
    public function getStructuredData(): array
    {
        return $this->structuredData;
    }
}