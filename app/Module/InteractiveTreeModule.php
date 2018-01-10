<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2018 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Fisharebest\Webtrees\Module;

use Fisharebest\Webtrees\Controller\ChartController;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Module\InteractiveTree\TreeView;

/**
 * Class InteractiveTreeModule
 * Tip : you could change the number of generations loaded before ajax calls both in individual page and in treeview page to optimize speed and server load
 */
class InteractiveTreeModule extends AbstractModule implements ModuleTabInterface, ModuleChartInterface {
	/** {@inheritdoc} */
	public function getTitle() {
		return /* I18N: Name of a module */ I18N::translate('Interactive tree');
	}

	/** {@inheritdoc} */
	public function getDescription() {
		return /* I18N: Description of the “Interactive tree” module */ I18N::translate('An interactive tree, showing all the ancestors and descendants of an individual.');
	}

	/** {@inheritdoc} */
	public function defaultTabOrder() {
		return 68;
	}

	/** {@inheritdoc} */
	public function getTabContent(Individual $individual) {
		$treeview        = new TreeView('tvTab');
		list($html, $js) = $treeview->drawViewport($individual, 3);

		return view('tabs/treeview', [
			'html'         => $html,
			'js'           => $js,
			'treeview_css' => $this->css(),
			'treeview_js'  => $this->js(),
		]);
	}

	/** {@inheritdoc} */
	public function hasTabContent(Individual $individual) {
		return true;
	}

	/** {@inheritdoc} */
	public function isGrayedOut(Individual $individual) {
		return false;
	}

	/** {@inheritdoc} */
	public function canLoadAjax() {
		return true;
	}

	/**
	 * Return a menu item for this chart.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 */
	public function getChartMenu(Individual $individual) {
		return new Menu(
			$this->getTitle(),
			'module.php?mod=tree&amp;mod_action=treeview&amp;rootid=' . $individual->getXref() . '&amp;ged=' . $individual->getTree()->getNameUrl(),
			'menu-chart-tree',
			['rel' => 'nofollow']
		);
	}

	/**
	 * Return a menu item for this chart - for use in individual boxes.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 */
	public function getBoxChartMenu(Individual $individual) {
		return $this->getChartMenu($individual);
	}

	/**
	 * This is a general purpose hook, allowing modules to respond to routes
	 * of the form module.php?mod=FOO&mod_action=BAR
	 *
	 * @param string $mod_action
	 */
	public function modAction($mod_action) {
		global $controller, $WT_TREE;

		switch ($mod_action) {
			case 'treeview':
				$controller = new ChartController;
				$tv         = new TreeView('tv');

				$person = $controller->getSignificantIndividual();

				list($html, $js) = $tv->drawViewport($person, 4);

				$controller
					->setPageTitle(I18N::translate('Interactive tree of %s', $person->getFullName()))
					->pageHeader()
					->addExternalJavascript($this->js())
					->addInlineJavascript($js)
					->addInlineJavascript('
					if (document.createStyleSheet) {
						document.createStyleSheet("' . $this->css() . '"); // For Internet Explorer
					} else {
						$("head").append(\'<link rel="stylesheet" type="text/css" href="' . $this->css() . '">\');
					}
				');

				echo view('interactive-tree-page', [
					'title'      => $controller->getPageTitle(),
					'individual' => $controller->root,
					'html'       => $html,
					//'css'        => $this->css(),
					//'js'         => $this->js(),
				]);

				break;

			case 'getDetails':
				header('Content-Type: text/html; charset=UTF-8');
				$pid        = Filter::get('pid', WT_REGEX_XREF);
				$i          = Filter::get('instance');
				$tv         = new TreeView($i);
				$individual = Individual::getInstance($pid, $WT_TREE);
				if ($individual) {
					echo $tv->getDetails($individual);
				}
				break;

			case 'getPersons':
				header('Content-Type: text/html; charset=UTF-8');
				$q  = Filter::get('q');
				$i  = Filter::get('instance');
				$tv = new TreeView($i);
				echo $tv->getPersons($q);
				break;

			default:
				http_response_code(404);
				break;
		}
	}

	/**
	 * URL for our style sheet.
	 *
	 * @return string
	 */
	public function css() {
		return WT_MODULES_DIR . $this->getName() . '/css/treeview.css';
	}

	/**
	 * URL for our JavaScript.
	 *
	 * @return string
	 */
	public function js() {
		return WT_MODULES_DIR . $this->getName() . '/js/treeview.js';
	}
}
