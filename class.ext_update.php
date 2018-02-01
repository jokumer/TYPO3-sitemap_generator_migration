<?php
namespace Jokumer\SitemapGeneratorMigration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class ext_update
 *
 * @package TYPO3
 * @subpackage tx_sitemapgeneratormigration
 * @author 2018 J.Kummer <typo3 et enobe dot de>, enobe.de
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ext_update
{
    /**
     * Extension name
     *
     * @var string Name of the extension this controller belongs to
     */
    protected $extensionName = 'SitemapGeneratorMigration';

    /**
     * Database connection
     *
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * Migration is necessary
     *
     * @var bool
     */
    protected $migrationIsNecessary = false;

    /**
     * Default change_frequency
     *
     * @var string 'yearly', 'monthly', 'weekly', 'daily', ... 'always' ...
     */
    protected $defaultChangeFrequency = 'monthly';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Get database connection
        $this->getDatabaseConnection();
    }

    /**
     * Main function, returning the HTML content
     *
     * @return string HTML
     */
    public function main()
    {
        $content = 'This update wizard can update all pages with values from EXT:dd_googlesitemap lastmod or just \'' . $this->defaultChangeFrequency . '\' for EXT:sitemap_generator.<br />';
        if (!$_POST['cmd']) {
            // Check if migration is necessary
            $content .= implode('<br />', $this->checkIfMigrationIsNecessary());
            // Offer to migrate
            if ($this->migrationIsNecessary) {
                $content .= '<br />Migration is necessary';
                $content .= '<br /><form action="" method="post"><input type="submit" name="cmd" value="Migrate now" class="btn btn-info" /></form>';
            } else {
                $content .= '<br /><span style="color:red"><strong>Migration is not necessary</strong>.</span><br />Ensure EXT:dd_googlesitemap has values in your pages table or empty DB:pages.sitemap_changefreq with <em>UPDATE pages SET sitemap_changefreq = \'\';</em>.<br />Otherwise you should uninstall and remove this extension again.';
            }
        } else {
            // Migrate data
            $migrationResultCount = $this->migrateData();
            if ($migrationResultCount) {
                $content .= '<span style="color:green">' . $migrationResultCount . ' pages updated with values from EXT:dd_googlesitemap lastmod or just \'' . $this->defaultChangeFrequency . '\'.</span><br />Clear cache to see results, and remove this extension from your installation again!';
            } else {
                $content .= '<span style="color:red">No pages updated with values from EXT:dd_googlesitemap lastmod.</span>';
            }
        }
        return $content;
    }

    /**
     * Get access
     * 
     * @return bool
     */
    public function access()
    {
        return true;
    }

    /**
     * Get database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Check if migration is necessary 
     *
     * @return string
     */
    protected function checkIfMigrationIsNecessary()
    {
        $content = [];
        // Count tx_ddgooglesitemap_lastmod
        $pagesWithTxDdGooglesitemapLastmodCount = $this->getPagesWithTxDdGooglesitemapLastmod(true);
        if ($pagesWithTxDdGooglesitemapLastmodCount) {
            $content[] = '<span style="color:green">' . $pagesWithTxDdGooglesitemapLastmodCount . ' pages with EXT:dd_googlesitemap lastmod values found.</span>';
        } else {
            $content[] = '<span style="color:red">No pages with EXT:dd_googlesitemap lastmod values found. Should exist to migrate for EXT:sitemap_generator.</span>';
        }
        // Count sitemap_changefreq
        $pagesWithTxSitemapGeneratorChangefrequenceCount = $this->getPagesWithTxSitemapGeneratorChangefrequence(true);
        if ($pagesWithTxSitemapGeneratorChangefrequenceCount) {
            $content[] = '<span style="color:red">' . $pagesWithTxSitemapGeneratorChangefrequenceCount . ' pages with EXT:sitemap_generator changefreq values found. Should be empty to migrate from EXT:dd_googlesitemap.</span>';
        } else {
            $content[] = '<span style="color:green">No pages with EXT:sitemap_generator changefreq values found.</span>';
        }
        // Count sitemap_changefreq empty
        $pagesWithEmptyTxSitemapGeneratorChangefrequenceCount = $this->getPagesWithEmptyTxSitemapGeneratorChangefrequence(true);
        if ($pagesWithEmptyTxSitemapGeneratorChangefrequenceCount) {
            $content[] = '<span style="color:green">' . $pagesWithEmptyTxSitemapGeneratorChangefrequenceCount . ' pages with empty EXT:sitemap_generator changefreq values found. Will be filled with EXT:dd_googlesitemap lastmod values or just \'' . $this->defaultChangeFrequency . '\'.</span>';
        } else {
            $content[] = '<span style="color:red">No pages with empty EXT:sitemap_generator changefreq values found. Should be empty to migrate from EXT:dd_googlesitemap.</span>';
        }
        // Set if migration is necessary
        if ($pagesWithTxDdGooglesitemapLastmodCount && !$pagesWithTxSitemapGeneratorChangefrequenceCount) {
            $this->migrationIsNecessary = true;
        }
        return $content;
    }

    /**
     * Migrate data
     *
     * @return 
     */
    protected function migrateData()
    {
        $count = 0;
        // Get tx_ddgooglesitemap_lastmod
        $pagesWithTxDdGooglesitemapLastmod = $this->getPagesWithTxDdGooglesitemapLastmod();
        // Count sitemap_changefreq
        $pagesWithTxSitemapGeneratorChangefrequenceCount = $this->getPagesWithTxSitemapGeneratorChangefrequence(true);
        // Update sitemap_changefreq with calculated change frequency from tx_ddgooglesitemap_lastmod
        if (!empty($pagesWithTxDdGooglesitemapLastmod) && !$pagesWithTxSitemapGeneratorChangefrequenceCount) {
            foreach ($pagesWithTxDdGooglesitemapLastmod as $page) {
                $dbUpdate = $this->databaseConnection->exec_UPDATEquery(
                    'pages',
                    'uid =' . $page['uid'],
                    ['sitemap_changefreq' => $this->calculateChangeFrequency($page)]
                );
                if ($dbUpdate) {
                    $count++;
                }
                $this->databaseConnection->sql_free_result($dbUpdate);
            }
        }
        // Get sitemap_changefreq empty
        $pagesWithEmptyTxSitemapGeneratorChangefrequence = $this->getPagesWithEmptyTxSitemapGeneratorChangefrequence();
        // Update empty sitemap_changefreq with defaultChangeFrequency
        if (!empty($pagesWithEmptyTxSitemapGeneratorChangefrequence)) {
            foreach ($pagesWithEmptyTxSitemapGeneratorChangefrequence as $page) {
                $dbUpdate = $this->databaseConnection->exec_UPDATEquery(
                    'pages',
                    'uid =' . $page['uid'],
                    ['sitemap_changefreq' => $this->defaultChangeFrequency]
                );
                if ($dbUpdate) {
                    $count++;
                }
                $this->databaseConnection->sql_free_result($dbUpdate);
            }
        }
        return $count;
    }

    /**
     * Get page entries with tx_ddgooglesitemap_lastmod values from EXT:dd_googlesitemap
     *
     * @param bool $returnCount
     * @return array $pages
     */
    protected function getPagesWithTxDdGooglesitemapLastmod($returnCount = false)
    {
        $select = 'uid, tx_ddgooglesitemap_lastmod, SYS_LASTCHANGED';
        $from = 'pages';
        $where = 'tx_ddgooglesitemap_lastmod != \'\'';
        if ($returnCount) {
            $dbCount = $this->databaseConnection->exec_SELECTcountRows('*',$from,$where);
            $this->databaseConnection->sql_free_result($dbCount);
            return $dbCount;
        } else {
            $pages = [];
            $dbSelect = $this->databaseConnection->exec_SELECTquery($select,$from,$where);
            while ($row = $this->databaseConnection->sql_fetch_assoc($dbSelect)) {
                $pages[] = $row;
            }
            $this->databaseConnection->sql_free_result($dbSelect);
            return $pages;
        }
    }

    /**
     * Get page entries with sitemap_changefreq values from EXT:sitemap_generator
     *
     * @param bool $returnCount
     * @return array $pages
     */
    protected function getPagesWithTxSitemapGeneratorChangefrequence($returnCount = false)
    {
        $select = 'uid, sitemap_changefreq';
        $from = 'pages';
        $where = 'sitemap_changefreq != \'\'';
        if ($returnCount) {
            $dbCount = $this->databaseConnection->exec_SELECTcountRows('*',$from,$where);
            $this->databaseConnection->sql_free_result($dbCount);
            return $dbCount;
        } else {
            $pages = [];
            $dbSelect = $this->databaseConnection->exec_SELECTquery($select,$from,$where);
            while ($row = $this->databaseConnection->sql_fetch_assoc($dbSelect)) {
                $pages[] = $row;
            }
            $this->databaseConnection->sql_free_result($dbSelect);
            return $pages;
        }
    }

    /**
     * Get page entries with empty sitemap_changefreq values from EXT:sitemap_generator
     *
     * @param bool $returnCount
     * @return array $pages
     */
    protected function getPagesWithEmptyTxSitemapGeneratorChangefrequence($returnCount = false)
    {
        $select = 'uid, sitemap_changefreq';
        $from = 'pages';
        $where = 'sitemap_changefreq = \'\' AND doktype IN (1,2,3,4,5,6,7)';
        if ($returnCount) {
            $dbCount = $this->databaseConnection->exec_SELECTcountRows('*',$from,$where);
            $this->databaseConnection->sql_free_result($dbCount);
            return $dbCount;
        } else {
            $pages = [];
            $dbSelect = $this->databaseConnection->exec_SELECTquery($select,$from,$where);
            while ($row = $this->databaseConnection->sql_fetch_assoc($dbSelect)) {
                $pages[] = $row;
            }
            $this->databaseConnection->sql_free_result($dbSelect);
            return $pages;
        }
    }

    /**
     * Calculates change frequency
     * From EXT:dd_googlesitemap https://github.com/dmitryd/typo3-dd_googlesitemap
     * Version 2.1.4
     *
     * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
     * @copyright (c) 2007-2014 Dmitry Dulepov <dmitry.dulepov@gmail.com>
     * @param array $pageInfo
     * @return string
     */
    protected function calculateChangeFrequency(array $pageInfo) {
        $timeValues = GeneralUtility::intExplode(',', $pageInfo['tx_ddgooglesitemap_lastmod']);
        // Remove zeros
        foreach ($timeValues as $k => $v) {
            if ($v == 0) {
                unset($timeValues[$k]);
            }
        }
        $timeValues[] = $pageInfo['SYS_LASTCHANGED'];
        $timeValues[] = time();
        sort($timeValues, SORT_NUMERIC);
        $sum = 0;
        for ($i = count($timeValues) - 1; $i > 0; $i--) {
            $sum += ($timeValues[$i] - $timeValues[$i - 1]);
        }
        $average = ($sum/(count($timeValues) - 1));
        return ($average >= 180*24*60*60 ? 'yearly' :
            ($average <= 24*60*60 ? 'daily' :
                ($average <= 60*60 ? 'hourly' :
                    ($average <= 14*24*60*60 ? 'weekly' : 'monthly'))));
    }
}
