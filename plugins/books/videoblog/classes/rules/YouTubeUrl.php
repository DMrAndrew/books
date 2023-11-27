<?php

namespace Books\Videoblog\Classes\rules;

class YouTubeUrl
{

    /**
     * @inheritDoc
     */
    public function validate($attribute, $value)
    {
        if (preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]{11})/", $value, $matches)) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'Формат ссылки не соотвествует YouTube. Скопируйте ссылку нажав на YouTube кнопку поделиться';
    }
}
