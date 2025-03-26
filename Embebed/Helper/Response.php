<?php

namespace EfipayPayment\Embebed\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;

class Response extends AbstractHelper
{
    /**
     * @throws Exception
     */
    public function generateResponse($response, $statusCode)
    {
        if($statusCode == 200){
            return $this->isSuccessResponse($response, $statusCode);
        }else{
            $this->isErrorResponse($response, $statusCode);
        }
    }
    public function isSuccessResponse($response, $statusCode) : array
    {
        return [
            'status' => $statusCode,
            'response' => $response
        ];
    }

    /**
     * @throws Exception
     */
    public function isErrorResponse($response, $statusCode)
    {
        $isError = $statusCode !== 200;
        return [
            'response' => $response,
            'status' => $statusCode,
        ];
    }
}
