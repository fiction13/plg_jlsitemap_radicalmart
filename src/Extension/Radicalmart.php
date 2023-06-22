<?php
/*
 * @package   JLSitemap - RadicalMart
 * @version   __DEPLOY_VERSION__
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2023 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

namespace Joomla\Plugin\JLSitemap\RadicalMart\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\RadicalMart\Site\Helper\AssociationHelper;
use Joomla\Component\RadicalMart\Site\Model\CategoriesModel;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

class Radicalmart extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var  boolean
	 *
	 * @since  0.0.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Method to get urls array
	 *
	 * @param   array     $urls    Urls array
	 * @param   Registry  $config  Component config
	 *
	 * @return  array Urls array with attributes
	 *
	 * @since  0.0.1
	 */
	public function onGetUrls(&$urls, $config)
	{
		$multilanguage = Multilanguage::isEnabled();

		// Categories
		if ($this->params->get('categories_enable', false))
		{
			$rows       = $this->getCategories();
			$changefreq = $this->params->get('categories_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('categories_priority', $config->get('priority', '0.5'));

			$categories_images_enable = $this->params->get('categories_images_enable', 1);

			// Add categories to arrays
			$categories = array();

			foreach ($rows as $row)
			{
				// Prepare category object
				$category             = new \stdClass();
				$category->type       = Text::_('PLG_JLSITEMAP_RADICALMART_TYPES_CATEGORY');
				$category->title      = $row->title;
				$category->loc        = $row->link;
				$category->changefreq = $changefreq;
				$category->priority   = $priority;
				$category->exclude    = false;
				$category->alternates = AssociationHelper::getAssociations($row->id) ?? false;

				if ($categories_images_enable)
				{
					$image = $row->media->get('image');

					if (!empty($image))
					{
						$category->images = array(Uri::root() . ltrim($image, '/'));
					}
				}

				// Add category to array
				$categories[] = $category;

				// Add category to alternates array
				if ($multilanguage && !empty($row->association) && empty($exclude))
				{
					if (!isset($alternates[$row->association]))
					{
						$alternates[$row->association] = array();
					}

					$alternates[$row->association][$row->language] = $loc;
				};
			}

			// Add alternates to categories
			if (!empty($alternates))
			{
				foreach ($categories as &$category)
				{
					$category->alternates = ($category->alternates) ? $alternates[$category->alternates] : false;
				}
			}

			// Add categories to urls
			$urls = array_merge($urls, $categories);
		}

		// Articles
		if ($this->params->get('products_enable', false))
		{
			$rows       = $this->getProducts();
			$changefreq = $this->params->get('products_changefreq', $config->get('changefreq', 'weekly'));
			$priority   = $this->params->get('productss_priority', $config->get('priority', '0.5'));

			$products_images_enable = $this->params->get('products_images_enable', 1);

			// Add products to urls arrays
			$products = array();

			foreach ($rows as $row)
			{
				// Prepare article object
				$product             = new \stdClass();
				$product->type       = Text::_('PLG_JLSITEMAP_RADICALMART_TYPES_PRODUCT');
				$product->title      = $row->title;
				$product->loc        = $row->link;
				$product->changefreq = $changefreq;
				$product->priority   = $priority;
				$product->exclude    = (!empty($exclude)) ? $exclude : false;
				$product->alternates = AssociationHelper::getAssociations($row->id, 'product') ?? false;

				if ($products_images_enable)
				{
					$image = $row->media->get('gallery.0.src');
					if (!empty($image))
					{
						$product->images = array(Uri::root() . ltrim($image, '/'));
					}
				}

				// Add article to array
				$products[] = $product;
			}

			// Add products to urls
			$urls = array_merge($urls, $products);
		}

		return $urls;
	}

	/**
	 * Get categories
	 *
	 * @return array Categories.
	 *
	 * @since 1.0.0
	 */
	public function getCategories()
	{

		if (!$model = Factory::getApplication()->bootComponent('com_radicalmart')->getMVCFactory()->createModel('Categories', 'Site', ['ignore_request' => true]))
		{
			throw new \Exception(Text::_('MOD_RADICALMART_CATEGORIES_ERROR_MODEL_NOT_FOUND'), 500);
		}

		$model->setState('params', Factory::getApplication()->getParams());
		$model->setState('filter.published', 1);

		// Check include
		if ($this->params->get('categories_include'))
		{
			$model->setState('filter.item_id', $this->params->get('categories_include'));
			$model->setState('filter.item_id.include', $this->params->get('categories_mode', true));
		}

		return $model->getItems();
	}

	/**
	 * Get products
	 *
	 * @return array Products.
	 *
	 * @since 1.0.0
	 */
	public function getProducts()
	{
		if (!$model = Factory::getApplication()->bootComponent('com_radicalmart')->getMVCFactory()->createModel('Products', 'Site', ['ignore_request' => true]))
		{
			throw new \Exception(Text::_('MOD_RADICALMART_CATEGORY_ERROR_MODEL_NOT_FOUND'), 500);
		}

		$model->setState('params', Factory::getApplication()->getParams());
		$model->setState('filter.categories', $this->params->get('products_categories_include'));
		$model->setState('filter.published', 1);

		// Get items
		$items = $model->getItems();

		return $items;
	}
}