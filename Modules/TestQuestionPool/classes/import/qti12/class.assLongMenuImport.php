<?php
require_once 'Modules/TestQuestionPool/classes/import/qti12/class.assQuestionImport.php';
require_once 'Modules/TestQuestionPool/classes/class.assLongMenu.php';

class assLongMenuImport extends assQuestionImport
{
	public $object;

	public function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		global $ilUser;

		unset($_SESSION["import_mob_xhtml"]);

		$presentation = $item->getPresentation();
		$duration = $item->getDuration();
		$questiontext = array();
		$seperate_question_field = $item->getMetadataEntry("question");
		$clozetext = array();
		$now = getdate();
		$created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
		$answers = array();
		$correct_answers = array();
		$presentation = $item->getPresentation();
		foreach ($presentation->order as $entry)
		{
			switch ($entry["type"])
			{
				case "material":

					$material = $presentation->material[$entry["index"]];
					if(preg_match('/\[Longmenu \d\]/', $this->object->QTIMaterialToString($material)))
					{
						$this->object->setLongMenuTextValue($this->object->QTIMaterialToString($material));
					}
					else
					{
						$this->object->setQuestion($this->object->QTIMaterialToString($material));
					}

					
					break;
			}
		}
		foreach ($item->resprocessing as $resprocessing)
		{
			foreach ($resprocessing->respcondition as $respcondition)
			{
				$correctness = 1;
				$conditionvar = $respcondition->getConditionvar();
				foreach ($conditionvar->order as $order)
				{
					switch ($order["field"])
					{
						case "varequal":
							$equals = $conditionvar->varequal[$order["index"]]->getContent();
							$gapident = $conditionvar->varequal[$order["index"]]->getRespident();
							$id = $this->getIdFromGapIdent($gapident);
							$answers[$id][] = $equals;
							break;
					}
				}
				foreach ($respcondition->setvar as $setvar)
				{
					if (strcmp($gapident, "") != 0)
					{
						if($setvar->getContent() > 0 )
						{
							$id = $this->getIdFromGapIdent($gapident);
							$correct_answers[$id][0][] = $equals;
							$correct_answers[$id][1] = $setvar->getContent();
							$_POST['points'][$id] = $setvar->getContent();
						}
					}
				}
				if (count($respcondition->displayfeedback))
				{
					foreach ($respcondition->displayfeedback as $feedbackpointer)
					{
						if (strlen($feedbackpointer->getLinkrefid()))
						{
							foreach ($item->itemfeedback as $ifb)
							{
								if (strcmp($ifb->getIdent(), "response_allcorrect") == 0)
								{
									// found a feedback for the identifier
									if (count($ifb->material))
									{
										foreach ($ifb->material as $material)
										{
											$feedbacksgeneric[1] = $material;
										}
									}
									if ((count($ifb->flow_mat) > 0))
									{
										foreach ($ifb->flow_mat as $fmat)
										{
											if (count($fmat->material))
											{
												foreach ($fmat->material as $material)
												{
													$feedbacksgeneric[1] = $material;
												}
											}
										}
									}
								}
								else if (strcmp($ifb->getIdent(), "response_onenotcorrect") == 0)
								{
									// found a feedback for the identifier
									if (count($ifb->material))
									{
										foreach ($ifb->material as $material)
										{
											$feedbacksgeneric[0] = $material;
										}
									}
									if ((count($ifb->flow_mat) > 0))
									{
										foreach ($ifb->flow_mat as $fmat)
										{
											if (count($fmat->material))
											{
												foreach ($fmat->material as $material)
												{
													$feedbacksgeneric[0] = $material;
												}
											}
										}
									}
								}
								else
								{
									// found a feedback for the identifier
									if (count($ifb->material))
									{
										foreach ($ifb->material as $material)
										{
											$feedbacks[$ifb->getIdent()] = $material;
										}
									}
									if ((count($ifb->flow_mat) > 0))
									{
										foreach ($ifb->flow_mat as $fmat)
										{
											if (count($fmat->material))
											{
												foreach ($fmat->material as $material)
												{
													$feedbacks[$ifb->getIdent()] = $material;
												}
											}
										}
									}

								}
							}
						}
					}
				}

			}
		}

		$sum = 0;
		foreach ($correct_answers as $row)
		{
			$sum 				+=  $row[1];
		}
		$this->object->setAnswers($answers);
		// handle the import of media objects in XHTML code
		foreach ($feedbacks as $ident => $material)
		{
			$m = $this->object->QTIMaterialToString($material);
			$feedbacks[$ident] = $m;
		}
		foreach ($feedbacksgeneric as $correctness => $material)
		{
			$m = $this->object->QTIMaterialToString($material);
			$feedbacksgeneric[$correctness] = $m;
		}
		
		$this->addGeneralMetadata($item);
		$this->object->setTitle($item->getTitle());
		$this->object->setNrOfTries($item->getMaxattempts());
		$this->object->setComment($item->getComment());
		$this->object->setAuthor($item->getAuthor());
		$this->object->setOwner($ilUser->getId());
		$this->object->setObjId($questionpool_id);
		$this->object->setEstimatedWorkingTime($duration["h"], $duration["m"], $duration["s"]);
		$_POST['hidden_text_files'] 		= json_encode($answers);
		$_POST['hidden_correct_answers'] 	= json_encode($correct_answers);
		$this->object->setPoints($sum);
		// additional content editing mode information
		$this->object->setAdditionalContentEditingMode(
			$this->fetchAdditionalContentEditingModeInformation($item)
		);
		$this->object->saveToDb();

		foreach ($feedbacks as $ident => $material)
		{
			$this->object->feedbackOBJ->importSpecificAnswerFeedback(
				$this->object->getId(), $ident, ilRTE::_replaceMediaObjectImageSrc($material, 1)
			);
		}
		foreach ($feedbacksgeneric as $correctness => $material)
		{
			$this->object->feedbackOBJ->importGenericFeedback(
				$this->object->getId(), $correctness, ilRTE::_replaceMediaObjectImageSrc($material, 1)
			);
		}
		$this->object->saveToDb();
		if (count($item->suggested_solutions))
		{
			foreach ($item->suggested_solutions as $suggested_solution)
			{
				$this->object->setSuggestedSolution($suggested_solution["solution"]->getContent(), $suggested_solution["gap_index"], true);
			}
			$this->object->saveToDb();
		}
	}

	private function getIdFromGapIdent($ident)
	{
		$id = preg_split('/_/', $ident);
		return $id[1] -1;
	}
}

