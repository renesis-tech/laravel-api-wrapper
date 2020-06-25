<?php


namespace Renesis\ApiWrapper\Wrapper;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ApiWrapper
{
    //@TODO: Implement Facades and Service Provider for batter implementation and make it more effective.
    private $statusCode;
    private $moreInfo;
    private $message;

    /**
     * If Validation is failed then variable value will be true
     * @var
     */
    private $validationFailed;

    /**
     * Response will be saved in case of failed validation
     *
     * @var JsonResponse
     */
    private $validationFailedResponse;


    //All Success Codes are in range of 200
    public const SUCCESS = 201;

    //All Error Codes are in range of 400
    public const VALIDATION_ERROR = 402;
    public const ACCESS_DENIED = 403;
    public const NOT_FOUND = 404;

    //All exception code are in range of 500
    public const SERVER_ERROR = 500;

    /**
     * Set status code for api response
     *
     * @param $code
     * @return $this
     * @author Syed Faisal <sfkazmi0@gmail.com>
     */
    public function setStatusCode(string $code)
    {
        if (config('renesis-api-wrapper.response_codes.'.$code)){
            $this->statusCode = config('renesis-api-wrapper.response_codes.'.$code);
        }

        return $this;
    }

    /**
     * Set Message key for api response
     *
     * @param $message
     * @return $this
     * @author Syed Faisal <sfkazmi0@gmail.com>
     */
    public function setMessage(string $message): ApiWrapper
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set More info key for api response
     *
     * @param $message
     * @return $this
     * @author Syed Faisal <sfkazmi0@gmail.com>
     */
    public function setMoreInfo(string $message): ApiWrapper
    {
        if (config('renesis-api-wrapper.more_info.'.$message)){
            $this->moreInfo = config('renesis-api-wrapper.more_info.'.$message);
        }else {
            $this->moreInfo = $message;
        }
        return $this;
    }

    /**
     * @return $this
     * @author Syed Faisal <sfkazmi0@gmail.com>
     */
    protected function getMoreInfoFromConfig(): ApiWrapper
    {
        $infoKey = '';
        $prefix = '';

        if (request()->route()->getPrefix()){
            $prefix = request()->route()->getPrefix();
            $infoKey.= str_replace('/','.',$prefix);
        }

        $infoKey.= '.'.str_replace($prefix.'/','',request()->route()->uri());
        $infoKey = str_replace('api.','',$infoKey);
        if (!is_null(config('renesis-api-wrapper.more_info.'.$infoKey))){
            $this->moreInfo = config('renesis-api-wrapper.more_info.'.$infoKey);
        }

        return $this;

    }

    /**
     * @return mixed
     * @author Syed Faisal <sfkazmi0@gmail.com>
     */
    protected function getMoreInfo()
    {
        if (is_null($this->moreInfo)){
            $this->getMoreInfoFromConfig();
        }

        return $this->moreInfo;
    }

    /**
     * Prepare json for api response
     *
     * @param array $data
     * @return JsonResponse
     * @author Syed Faisal <sfkazmi0@gmail.com>
     */
    public function response($data = []): JsonResponse
    {
        if (is_null($this->moreInfo) && request()){
            $this->getMoreInfoFromConfig();
        }

        return response()->json([
            'status' => 200,
            'code' => $this->statusCode ?? self::SUCCESS,
            'message' => $this->message ?? 'Success',
            'more_info' => $this->moreInfo,
            'data' => $data
        ],200);
    }

    /**
     * Validate Data and set validation failed response
     *
     * @param $data
     * @param $rules
     * @param string $failInfo
     * @return $this
     * @author Syed Faisal <sfkazmi0@gmail.com>
     */
    public function validateRequest(array $data,array $rules,string $apiInfo=""): ApiResponse
    {
        $validator = Validator::make($data,$rules);
        $this->validationFailed = false;

        if ($apiInfo == "") {
            $apiInfo = $this->getMoreInfo();
        }

        if ($this->statusCode == null){
            $this->setStatusCode(self::VALIDATION_ERROR);
        }

        if ($this->message == null){
            $this->setMessage('Validation Failed');
        }

        if ($validator->fails()){
            $this->validationFailed = true;
            $errors = $validator->errors()->toArray();
            $errors = array_values($errors);
            $errors = call_user_func_array('array_merge', $errors);
            $this->validationFailedResponse = $this->setMoreInfo($apiInfo)
                ->response($errors);
        }

        return $this;
    }

    /**
     * Getter to check if validation is failed
     *
     * @return boolean
     * @author Syed Faisal <sfkazmi0@gmail.com>
     */
    public function validationFailed() : bool
    {
        return $this->validationFailed ?? false;
    }

    /**
     * Getter for validation failed response
     *
     * @return JsonResponse
     * @author Syed Faisal <sfkazmi0@gmail.com>
     */
    public function validationFailedResponse(): JsonResponse
    {
        return $this->validationFailedResponse;
    }

    /**
     * Response in case any exception occur during execution of code in api
     *
     * @param \Exception $exception
     * @return JsonResponse
     * @author Syed Faisal <sfkazmi0@gmail.com>
     */
    public function exceptionResponse(\Exception $exception): JsonResponse
    {
        return $this->setStatusCode(self::SERVER_ERROR)
            ->setMessage('Something Went Wrong, We are working hard to fix it')
            ->setMoreInfo('Message: '.$exception->getMessage(). ',Line:'.$exception->getLine().', File:'.$exception->getFile())
            ->response(['inputData' => request()->all()]);
    }

    public static function unauthenticated()
    {
        return response()->json([
            'status' => 200,
            'code' => self::ACCESS_DENIED,
            'message' => "User is not authenticated, Authentication Required"
        ]);
    }
}
