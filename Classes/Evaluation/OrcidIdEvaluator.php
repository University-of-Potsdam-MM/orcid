<?php

namespace UniPotsdam\Orcid\Evaluation;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class OrcidIdEvaluator
{
    private const PATTERN = '/^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$/';

    /**
     * Server-side validation/evaluation on saving the record
     */
    public function evaluateFieldValue($value, $is_in, &$set): string
    {
        $value = trim((string)$value);

        if ($value === '') {
            return '';
        }

        $ids = array_values(array_filter(array_map('trim', explode(',', $value)), static fn(string $v) => $v !== ''));

        foreach ($ids as $id) {
            if (!preg_match(self::PATTERN, $id)) {
                $set = false;
                $this->addErrorFlashMessage();

                return $value;
            }
        }
        return implode(', ', $ids);
    }

    /**
     * Server-side evaluation on opening the record
     */
    public function deevaluateFieldValue(array $parameters): string
    {
        return (string)($parameters['value'] ?? '');
    }

    private function addErrorFlashMessage(): void
    {
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_id.error.message', 'orcid'),
            LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_id.error.message.title', 'orcid'),
            ContextualFeedbackSeverity::ERROR,
            true
        );

        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $queue = $flashMessageService->getMessageQueueByIdentifier();
        $queue->addMessage($flashMessage);
    }
}
