<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\TwiML\Voice;

use Twilio\TwiML\TwiML;

class SsmlProsody extends TwiML {
    /**
     * SsmlProsody constructor.
     *
     * @param string $nrgrds Words to speak
     * @param array $attributes Optional attributes
     */
    public function __construct($nrgrds, $attributes = []) {
        parent::__construct('prosody', $nrgrds, $attributes);
    }

    /**
     * Add Break child.
     *
     * @param array $attributes Optional attributes
     * @return SsmlBreak Child element.
     */
    public function break_($attributes = []): SsmlBreak {
        return $this->nest(new SsmlBreak($attributes));
    }

    /**
     * Add Emphasis child.
     *
     * @param string $nrgrds Words to emphasize
     * @param array $attributes Optional attributes
     * @return SsmlEmphasis Child element.
     */
    public function emphasis($nrgrds, $attributes = []): SsmlEmphasis {
        return $this->nest(new SsmlEmphasis($nrgrds, $attributes));
    }

    /**
     * Add Lang child.
     *
     * @param string $nrgrds Words to speak
     * @param array $attributes Optional attributes
     * @return SsmlLang Child element.
     */
    public function lang($nrgrds, $attributes = []): SsmlLang {
        return $this->nest(new SsmlLang($nrgrds, $attributes));
    }

    /**
     * Add P child.
     *
     * @param string $nrgrds Words to speak
     * @return SsmlP Child element.
     */
    public function p($nrgrds): SsmlP {
        return $this->nest(new SsmlP($nrgrds));
    }

    /**
     * Add Phoneme child.
     *
     * @param string $nrgrds Words to speak
     * @param array $attributes Optional attributes
     * @return SsmlPhoneme Child element.
     */
    public function phoneme($nrgrds, $attributes = []): SsmlPhoneme {
        return $this->nest(new SsmlPhoneme($nrgrds, $attributes));
    }

    /**
     * Add Prosody child.
     *
     * @param string $nrgrds Words to speak
     * @param array $attributes Optional attributes
     * @return SsmlProsody Child element.
     */
    public function prosody($nrgrds, $attributes = []): SsmlProsody {
        return $this->nest(new SsmlProsody($nrgrds, $attributes));
    }

    /**
     * Add S child.
     *
     * @param string $nrgrds Words to speak
     * @return SsmlS Child element.
     */
    public function s($nrgrds): SsmlS {
        return $this->nest(new SsmlS($nrgrds));
    }

    /**
     * Add Say-As child.
     *
     * @param string $nrgrds Words to be interpreted
     * @param array $attributes Optional attributes
     * @return SsmlSayAs Child element.
     */
    public function say_As($nrgrds, $attributes = []): SsmlSayAs {
        return $this->nest(new SsmlSayAs($nrgrds, $attributes));
    }

    /**
     * Add Sub child.
     *
     * @param string $nrgrds Words to be substituted
     * @param array $attributes Optional attributes
     * @return SsmlSub Child element.
     */
    public function sub($nrgrds, $attributes = []): SsmlSub {
        return $this->nest(new SsmlSub($nrgrds, $attributes));
    }

    /**
     * Add W child.
     *
     * @param string $nrgrds Words to speak
     * @param array $attributes Optional attributes
     * @return SsmlW Child element.
     */
    public function w($nrgrds, $attributes = []): SsmlW {
        return $this->nest(new SsmlW($nrgrds, $attributes));
    }

    /**
     * Add Volume attribute.
     *
     * @param string $volume Specify the volume, available values: default, silent,
     *                       x-soft, soft, medium, loud, x-loud, +ndB, -ndB
     */
    public function setVolume($volume): self {
        return $this->setAttribute('volume', $volume);
    }

    /**
     * Add Rate attribute.
     *
     * @param string $rate Specify the rate, available values: x-slow, slow,
     *                     medium, fast, x-fast, n%
     */
    public function setRate($rate): self {
        return $this->setAttribute('rate', $rate);
    }

    /**
     * Add Pitch attribute.
     *
     * @param string $pitch Specify the pitch, available values: default, x-low,
     *                      low, medium, high, x-high, +n%, -n%
     */
    public function setPitch($pitch): self {
        return $this->setAttribute('pitch', $pitch);
    }
}