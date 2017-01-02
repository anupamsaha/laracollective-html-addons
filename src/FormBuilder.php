<?php

namespace AnupamSaha\LaravelExtra\Html;

use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Routing\UrlGenerator;
use Collective\Html\FormBuilder as BaseFormBuilder;
use AnupamSaha\LaravelExtra\Html\Exceptions\EncryptException;
use AnupamSaha\LaravelExtra\Html\Exceptions\DecryptException;
use AnupamSaha\LaravelExtra\Html\Exceptions\EncodingException;
use AnupamSaha\LaravelExtra\Html\Exceptions\DecodingException;

class FormBuilder extends BaseFormBuilder
{

    /**
     * Form protector key identifier
     *
     * @var string
     */
    protected $formProtectKey;

    /**
     * Name of the hidden variable that protects the hidden fields from tampering
     *
     * @var string
     */
    protected $hiddenProtector;

    /**
     * Request segment key for protected variables
     *
     * @var string
     */
    protected $segmentKey;

    /**
     * Timestamp key
     *
     * @var string
     */
    protected $tsKey;

    /**
     * Time to create the encryption
     *
     * @var string
     */
    protected $timestamp;

    /**
     * Protected fields
     *
     * @var array
     */
    protected $protectedFields = [];

    /**
     * Other protected fields as set by Form::hidden() method
     *
     * @var array
     */
    protected $otherProtectedFields = [];

    /**
     * Create a new form builder instance.
     *
     * @param  \Collective\Html\HtmlBuilder                 $html
     * @param  \Illuminate\Contracts\Routing\UrlGenerator   $url
     * @param  string                                       $csrfToken
     *
     * @return void
     */
    public function __construct(HtmlBuilder $html, UrlGenerator $url, $csrfToken)
    {
        $this->formProtectKey = Config::get('htmladdons.form_protect_key', '__protect');
        $this->hiddenProtector = Config::get('htmladdons.hidden_protector', '__data');
        $this->segmentKey = Config::get('htmladdons.segment_key', 'segements');
        $this->tsKey = Config::get('htmladdons.timestamp_key', '__tx');

        parent::__construct($html, $url, $csrfToken);
    }

    /**
     * Check whether last JSON operation was success or not
     *
     * @return boolean
     */
    protected function isJsonOperationValid()
    {
        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Encodes and returns the data
     *
     * @param string $data
     * @return string
     * @throws AnupamSaha\LaravelExtra\Html\Exceptions\EncodingException
     */
    protected function encode($data)
    {
        $data[$this->tsKey] = $this->timestamp;

        $jsonEncoded = json_encode($data);

        if (!$this->isJsonOperationValid()) {
            throw new EncodingException('JSON encoding failed');
        }

        return $jsonEncoded;
    }

    /**
     * Decodes the data from json encoded string
     *
     * @param string $data
     * @return string
     * @throws AnupamSaha\LaravelExtra\Html\Exceptions\DecodingException
     */
    protected function decode($data)
    {
        $jsonDecoded = json_decode($data, true);

        if (!$this->isJsonOperationValid()) {
            throw new DecodingException('JSON decoding failed');
        }

        return $jsonDecoded;
    }

    /**
     * Create the encrypted value from data
     *
     * @param mixed $data
     * @return string
     * @throws AnupamSaha\LaravelExtra\Html\Exceptions\EncryptException
     */
    protected function encrypt($data)
    {
        try {
            $value = Crypt::encrypt($this->encode($data));

            return $value;
        } catch (Exception $ex) {
            throw new EncryptException($ex->getMessage());
        }
    }

    /**
     * Decrypt the data and returns it
     *
     * @param string $data
     * @return mixed
     * @throws DecodingException
     * @throws DecryptException
     */
    public function clean($data)
    {
        try {
            $value = $this->decode(Crypt::decrypt($data));

            return $value;
        } catch (DecodingException $dx) {
            throw $dx;
        } catch (Exception $ex) {
            throw new DecryptException($ex->getMessage());
        }
    }

    /**
     * Open up a new HTML form
     *
     * @param array $options
     * @return string
     */
    public function open(array $options = [])
    {
        $this->protectedFields = array_merge(
            ['_token' => csrf_token()],
            array_pull($options, $this->formProtectKey, [])
        );

        $timeStamp = '';
        if (is_array($this->protectedFields) && count($this->protectedFields) > 0) {
            $this->timestamp = microtime(true);
            $timeStamp = $this->hidden($this->tsKey, $this->timestamp);
        }

        $formTag = parent::open($options) . $timeStamp;

        return $formTag;
    }

    /**
     * Generate a hidden field with the current CSRF token.
     *
     * @return string
     */
    public function token()
    {
        $tokenTag = parent::token();

        return PHP_EOL . $tokenTag;
    }

    /**
     * Populates array of hidden fields those are passed in Form::hidden() method
     *
     * @param string $name
     * @param mixed $value
     * @param bool $valueIsArray
     * @return void
     */
    protected function populateExtraHidden($name, $value, $valueIsArray = false)
    {
        if ($valueIsArray) {
            if (isset($this->otherProtectedFields[$name])) {
                array_push($this->otherProtectedFields[$name], $value);
            } else {
                $this->otherProtectedFields[$name] = [$value];
            }
        } else {
            $this->otherProtectedFields = [$name => $value];
        }
    }

    /**
     * {@inherited}
     */
    public function hidden($name, $value = null, $options = [])
    {
        if ($name === $this->hkey() || $name === $this->tskey()) {
            return parent::hidden($name, $value, $options);
        }

        $valueIsArray = false;
        if (($actualName = strstr($name, '[', true))) {
            $valueIsArray = true;
        } else {
            $actualName = $name;
        }

        if (! in_array($actualName, array_keys($this->protectedFields)) && $value !== null) {
            $this->populateExtraHidden($actualName, $value, $valueIsArray);
        }

        return parent::hidden($name, $value, $options);
    }

    /**
     * Returns the hidden field with protected key and encrypted values
     *
     * @return string
     */
    protected function protect()
    {
        $protected = '';
        $list = $this->protectedFields + $this->otherProtectedFields;

        if (is_array($list) && count($list) > 0) {
            $protected = $this->hidden(
                    $this->hiddenProtector, $this->encrypt([
                        $this->pkey() => $list
                    ])
                ) . PHP_EOL;
        }

        return $protected;
    }

    /**
     * Generate hidden fields
     *
     * @param string $name
     * @param int|string|array $value
     * @return string
     */
    protected function generateAutohidden($name, $value)
    {
        if (! is_array($value)) {
            $value = [$value];
        }

        $count = count($value);

        $field = '';

        // added id in hidden
        $options = [];
        if (!isset($options['id'])) {
          $options['id'] = $name;
        }


        foreach($value as $key => $val) {
            if (! is_null($val)) {
                $field .= $this->hidden($name.($count > 1 ? "[$key]" : ''), $val, $options);
            }
        }

        return $field;
    }

    /**
     * Generate hidden fields automatically from the protected list
     *
     * @return string
     */
    protected function autohidden()
    {
        $autoHidden = '';

        if (is_array($this->protectedFields) && count($this->protectedFields) > 0) {
            foreach ($this->protectedFields as $key => $value) {
                if ($key !== $this->segmentKey && $key !== '_token') {
                    $autoHidden .= $this->generateAutohidden($key, $value) . PHP_EOL;
                }
            }
        }

        return $autoHidden;
    }

    /**
     * Closes the current form
     *
     * @return string
     */
    public function close()
    {
        $close = $this->autohidden() . $this->protect() . parent::close();

        return $close;
    }

    /**
     * Returns form protector key
     *
     * @return string
     */
    public function pkey()
    {
        return $this->formProtectKey;
    }

    /**
     * Returns hidden protector key
     *
     * @return string
     */
    public function hkey()
    {
        return $this->hiddenProtector;
    }

    /**
     * Returns segment key
     *
     * @return string
     */
    public function skey()
    {
        return $this->segmentKey;
    }

    /**
     * Returns timestamp key
     *
     * @return string
     */
    public function tskey()
    {
        return $this->tsKey;
    }
}
