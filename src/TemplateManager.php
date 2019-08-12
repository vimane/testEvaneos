<?php
namespace Core;

use Core\Entity\Template;
use Core\Context\ApplicationContext;
use Core\Entity\Quote;
use Core\Repository\QuoteRepository;
use Core\Repository\SiteRepository;
use Core\Repository\DestinationRepository;
use Core\Entity\User;


class TemplateManager
{
    public $subject;
    public $content;

    public function getTemplateComputed(Template $tpl, array $data)
    {
        if (!$tpl) {
            throw new \RuntimeException('no tpl given');
        }

      /*
        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject, $data);
        $replaced->content = $this->computeText($replaced->content, $data);*/
        $this->subject = $this->computeText($tpl->subject, $data);
        $this->content =  $this->computeText($tpl->content, $data);

        return $this;
    }

    /**
    *   computeText : traitement du type de donnée
    *   For new type of data:
    *   1 : import Core\Entity\Type
    *   2: create private method with name "type" for new traitment
    *@param  string $text
    *@param  array $data
    *@return string
    */
    private function computeText($text, array $data):string
    {
        $APPLICATION_CONTEXT = ApplicationContext::getInstance();

        //init $data['user'] if null
        if(!isset($data['user']))
            $data['user'] = $APPLICATION_CONTEXT->getCurrentUser();

        foreach ($data as $key => $value) {
            $class = get_class($value);
            if($value instanceof $class )
                $text = $this->$key($text, $value); 
        }

        return $text;
    }

    /**
    * method quote: traitment for $data['quote']
    *@param string $test
    *@param Quote $quote
    *@return string
    */
    private function quote(string $text, $quote): string
    {
         $_quoteFromRepository = QuoteRepository::getInstance()->getById($quote->id);
            $usefulObject = SiteRepository::getInstance()->getById($quote->siteId);
            $destinationOfQuote = DestinationRepository::getInstance()->getById($quote->destinationId);

            if(strpos($text, '[quote:destination_link]') !== false){
                $destination = DestinationRepository::getInstance()->getById($quote->destinationId);
            }

            $containsSummaryHtml = strpos($text, '[quote:summary_html]');
            $containsSummary     = strpos($text, '[quote:summary]');

            if ($containsSummaryHtml !== false || $containsSummary !== false) {
                if ($containsSummaryHtml !== false) {
                    $text = str_replace(
                        '[quote:summary_html]',
                        Quote::renderHtml($_quoteFromRepository),
                        $text
                    );
                }
                if ($containsSummary !== false) {
                    $text = str_replace(
                        '[quote:summary]',
                        Quote::renderText($_quoteFromRepository),
                        $text
                    );
                }
            }

            (strpos($text, '[quote:destination_name]') !== false) and $text = str_replace('[quote:destination_name]',$destinationOfQuote->countryName,$text);

        if (isset($destination))
            $text = str_replace('[quote:destination_link]', $usefulObject->url . '/' . $destination->countryName . '/quote/' . $_quoteFromRepository->id, $text);
        else
            $text = str_replace('[quote:destination_link]', '', $text);

        return $text;
  
    }


    /**
    * method user: traitment for $data['user']
    *@param string $test
    *@param User $user
    *@return string
    */
    private function user(string $text, $_user):string
    {

         (strpos($text, '[user:first_name]') !== false) and $text = str_replace('[user:first_name]'       , ucfirst(mb_strtolower($_user->firstname)), $text);
         return $text;
    }


}
