<?php

namespace Browscap\Generator;

class BrowscapIniGenerator implements GeneratorInterface
{
    /**
     * @var bool
     */
    private $quoteStringProperties;

    /**
     * @var bool
     */
    private $includeExtraProperties;

    /**
     * @var bool
     */
    private $liteOnly;

    /**
     * @var array
     */
    private $collectionData;

    /**
     * @var array
     */
    private $comments = array();

    /**
     * @var array
     */
    private $versionData = array();

    /**
     * Set defaults
     */
    public function __construct()
    {
        $this->quoteStringProperties = false;
        $this->includeExtraProperties = true;
        $this->liteOnly = false;
    }

    /**
     * Set the data collection
     *
     * @param array $collectionData
     * @return \Browscap\Generator\BrowscapIniGenerator
     */
    public function setCollectionData(array $collectionData)
    {
        $this->collectionData = $collectionData;
        return $this;
    }

    /**
     * Get the data collection
     *
     * @throws \LogicException
     * @return array
     */
    public function getCollectionData()
    {
        if (!isset($this->collectionData)) {
            throw new \LogicException("Data collection has not been set yet - call setDataCollection");
        }

        return $this->collectionData;
    }

    /**
     * @param array $comments
     *
     * @return \Browscap\Generator\BrowscapIniGenerator
     */
    public function setComments(array $comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * @return array
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param array $versionData
     *
     * @return \Browscap\Generator\BrowscapIniGenerator
     */
    public function setVersionData(array $versionData)
    {
        $this->versionData = $versionData;

        return $this;
    }

    /**
     * @return array
     */
    public function getVersionData()
    {
        return $this->versionData;
    }

    /**
     * Set the options for generation
     *
     * @param boolean $quoteStringProperties
     * @param boolean $includeExtraProperties
     * @param boolean $liteOnly
     * @return \Browscap\Generator\BrowscapIniGenerator
     */
    public function setOptions($quoteStringProperties, $includeExtraProperties, $liteOnly)
    {
        $this->quoteStringProperties = (bool)$quoteStringProperties;
        $this->includeExtraProperties = (bool)$includeExtraProperties;
        $this->liteOnly = (bool)$liteOnly;

        return $this;
    }

    /**
     * Generate and return the formatted browscap data
     *
     * @return string
     */
    public function generate()
    {
        return $this->render(
            $this->collectionData,
            $this->renderHeader(),
            array_keys(array('Parent' => '') + $this->collectionData['DefaultProperties'])
        );
    }

    /**
     * Generate the header
     *
     * @return string
     */
    private function renderHeader()
    {
        $header = '';

        foreach ($this->getComments() as $comment) {
            $header .= ';;; ' . $comment . "\n";
        }

        $header .= "\n";

        $header .= $this->renderVersion();

        return $header;
    }

    /**
     * Get the type of a property
     *
     * @param string $propertyName
     * @throws \Exception
     * @return string
     */
    public function getPropertyType($propertyName)
    {
        switch ($propertyName) {
            case 'Comment':
            case 'Browser':
            case 'Platform':
            case 'Platform_Description':
            case 'Device_Name':
            case 'Device_Maker':
            case 'RenderingEngine_Name':
            case 'RenderingEngine_Description':
                return 'string';
            case 'Parent':
            case 'Platform_Version':
            case 'RenderingEngine_Version':
                return 'generic';
            case 'Version':
            case 'MajorVer':
            case 'MinorVer':
            case 'CssVersion':
            case 'AolVersion':
                return 'number';
            case 'Alpha':
            case 'Beta':
            case 'Win16':
            case 'Win32':
            case 'Win64':
            case 'Frames':
            case 'IFrames':
            case 'Tables':
            case 'Cookies':
            case 'BackgroundSounds':
            case 'JavaScript':
            case 'VBScript':
            case 'JavaApplets':
            case 'ActiveXControls':
            case 'isMobileDevice':
            case 'isSyndicationReader':
            case 'Crawler':
                return 'boolean';
            default:
                throw new \InvalidArgumentException("Property {$propertyName} did not have a defined property type");
        }
    }

    /**
     * Determine if the specified property is an "extra" property (that should
     * be included in the "full" versions of the files)
     *
     * @param string $propertyName
     * @return boolean
     */
    public function isExtraProperty($propertyName)
    {
        switch ($propertyName) {
            case 'Device_Name':
            case 'Device_Maker':
            case 'Platform_Description':
            case 'RenderingEngine_Name':
            case 'RenderingEngine_Version':
            case 'RenderingEngine_Description':
                return true;
            default:
                return false;
        }
    }

    /**
     * renders all found useragents into a string
     *
     * @param $allDivisions
     * @param $output
     * @param $allProperties
     *
     * @return string
     */
    private function render($allDivisions, $output, $allProperties)
    {
        foreach ($allDivisions as $key => $properties) {
            if (!isset($properties['Version'])) {
                continue;
            }

            if (!isset($properties['Parent'])
                && 'DefaultProperties' !== $key
                && '*' !== $key
            ) {
                continue;
            }

            if ($this->liteOnly && (!isset($properties['lite']) || !$properties['lite'])) {
                continue;
            }

            if ('DefaultProperties' !== $key && '*' !== $key) {
                if (!isset($allDivisions[$properties['Parent']])) {
                    continue;
                }

                $parent = $allDivisions[$properties['Parent']];
            } else {
                $parent = array();
            }

            if (isset($parent['Version'])) {
                $completeVersions = explode('.', $parent['Version'], 2);

                $parent['MajorVer'] = (string) $completeVersions[0];

                if (isset($completeVersions[1])) {
                    $parent['MinorVer'] = (string) $completeVersions[1];
                } else {
                    $parent['MinorVer'] = 0;
                }
            }

            $propertiesToOutput = $properties;

            foreach ($propertiesToOutput as $property => $value) {
                if (!isset($parent[$property])) {
                    continue;
                }

                $parentProperty = $parent[$property];

                switch ((string) $parentProperty) {
                    case 'true':
                        $parentProperty = true;
                        break;
                    case 'false':
                        $parentProperty = false;
                        break;
                    default:
                        $parentProperty = trim($parentProperty);
                        break;
                }

                if ($parentProperty != $value) {
                    continue;
                }

                unset($propertiesToOutput[$property]);
            }

            // create output - php

            if ('DefaultProperties' === $key
                || '*' === $key || empty($properties['Parent'])
                || 'DefaultProperties' == $properties['Parent']
            ) {
                $output .= ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; ' . $key . "\n\n";
            }

            $output .= '[' . $key . ']' . "\n";

            foreach ($allProperties as $property) {
                if (!isset($propertiesToOutput[$property])) {
                    continue;
                }

                if ('lite' === $property || 'sortIndex' === $property || 'Parents' === $property) {
                    continue;
                }

                $value       = $propertiesToOutput[$property];
                $valueOutput = $value;

                switch ($this->getPropertyType($property)) {
                    case 'string':
                        if ($this->quoteStringProperties) {
                            $valueOutput = '"' . $value . '"';
                        }
                        break;
                    case 'boolean':
                        if (true === $value || $value === 'true') {
                            $valueOutput = 'true';
                        } elseif (false === $value || $value === 'false') {
                            $valueOutput = 'false';
                        }
                        break;
                    case 'generic':
                    case 'number':
                    default:
                        // nothing t do here
                        break;
                }

                $output .= $property . '=' . $valueOutput . "\n";
            }

            $output .= "\n";
        }

        return $output;
    }

    /**
     * renders the version information
     *
     * @return string
     */
    private function renderVersion()
    {
        $header = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version' . "\n\n";

        $header .= '[GJK_Browscap_Version]' . "\n";

        $versionData = $this->getVersionData();

        if (!isset($versionData['version'])) {
            $versionData['version'] = '0';
        }

        if (!isset($versionData['released'])) {
            $versionData['released'] = '';
        }

        $header .= 'Version=' . $versionData['version'] . "\n";
        $header .= 'Released=' . $versionData['released'] . "\n\n";

        return $header;
    }
}
