<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package     Modules/Test
 */
class ilTestQuestionSideListGUI
{
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	
	/**
	 * @var ilLanguage
	 */
	protected $lng;

	/**
	 * @var ilTestPlayerAbstractGUI
	 */
	private $targetGUI;
	
	/**
	 * @var array
	 */
	private $questionSummaryData;

	/**
	 * @var integer
	 */
	private $currentSequenceElement;

	/**
	 * @var bool
	 */
	private $disabled;

	/**
	 * @param ilCtrl $ctrl
	 * @param ilLanguage $lng
	 */
	public function __construct(ilCtrl $ctrl, ilLanguage $lng)
	{
		$this->ctrl = $ctrl;
		$this->lng = $lng;
			
		$this->questionSummaryData = array();
		$this->currentSequenceElement = null;
		$this->disabled = false;
	}

	/**
	 * @return ilTestPlayerAbstractGUI
	 */
	public function getTargetGUI()
	{
		return $this->targetGUI;
	}

	/**
	 * @param ilTestPlayerAbstractGUI $targetGUI
	 */
	public function setTargetGUI($targetGUI)
	{
		$this->targetGUI = $targetGUI;
	}

	/**
	 * @return array
	 */
	public function getQuestionSummaryData()
	{
		return $this->questionSummaryData;
	}

	/**
	 * @param array $questionSummaryData
	 */
	public function setQuestionSummaryData($questionSummaryData)
	{
		$this->questionSummaryData = $questionSummaryData;
	}

	/**
	 * @return int
	 */
	public function getCurrentSequenceElement()
	{
		return $this->currentSequenceElement;
	}

	/**
	 * @param int $currentSequenceElement
	 */
	public function setCurrentSequenceElement($currentSequenceElement)
	{
		$this->currentSequenceElement = $currentSequenceElement;
	}

	/**
	 * @return boolean
	 */
	public function isDisabled()
	{
		return $this->disabled;
	}

	/**
	 * @param boolean $disabled
	 */
	public function setDisabled($disabled)
	{
		$this->disabled = $disabled;
	}

	/**
	 * @return ilPanelGUI
	 */
	private function buildPanel()
	{
		require_once 'Services/UIComponent/Panel/classes/class.ilPanelGUI.php';
		$panel = ilPanelGUI::getInstance();
		$panel->setHeadingStyle(ilPanelGUI::HEADING_STYLE_SUBHEADING);
		$panel->setPanelStyle(ilPanelGUI::PANEL_STYLE_SECONDARY);
		$panel->setHeading($this->lng->txt('list_of_questions'));
		return $panel;
	}

	/**
	 * @return string
	 */
	private function renderList()
	{
		$tpl = new ilTemplate('tpl.il_as_tst_list_of_questions_short.html', true, true, 'Modules/Test');

		foreach( $this->getQuestionSummaryData() as $row )
		{
			$title = ilUtil::prepareFormOutput($row['title']);

			if( strlen($row['description']) )
			{
				$description = " title=\"{$row['description']}\" ";
			}
			else
			{
				$description = "";
			}

			$active = ($row['sequence'] == $this->getCurrentSequenceElement()) ? ' active' : '';
			
			$class = (
				$row['walked_through'] ? 'answered'.$active : 'unanswered'.$active
			);
				
			if( $this->isDisabled() )
			{
				$tpl->setCurrentBlock('disabled_entry');
				$tpl->setVariable('CLASS', $class);
				$tpl->setVariable('ITEM', $title);
				$tpl->setVariable('DESCRIPTION', $description);
				$tpl->parseCurrentBlock();
			}
			else
			{
				$this->ctrl->setParameter($this->getTargetGUI(), 'sequence', $row['sequence']);
				$href = $this->ctrl->getLinkTarget($this->getTargetGUI(), ilTestPlayerCommands::SHOW_QUESTION);
				$this->ctrl->setParameter($this->getTargetGUI(), 'sequence', $this->getCurrentSequenceElement());

				$tpl->setCurrentBlock('linked_entry');
				$tpl->setVariable('HREF', $href);
				$tpl->setVariable('CLASS', $class);
				$tpl->setVariable('ITEM', $title);
				$tpl->setVariable("DESCRIPTION", $description);
				$tpl->parseCurrentBlock();

			}

			$tpl->setCurrentBlock('item');
		}

		return $tpl->get();
	}

	/**
	 * @return string
	 */
	public function getHTML()
	{
		$panel = $this->buildPanel();
		$panel->setBody($this->renderList());
		return $panel->getHTML();
	}
}