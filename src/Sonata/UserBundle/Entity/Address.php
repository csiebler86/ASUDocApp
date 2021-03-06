<?php

namespace Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContext;

/**
 * Sonata\UserBundle\Entity\Address
 *
 * @ORM\Table(name="addresses")
 * @ORM\Entity()
 * @Assert\Callback(methods={"isZipcodeValid"})
 * @Assert\Callback(methods={"isStateValid"})
 */
class Address {

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Please enter a street address")
     * @Assert\Length(
     *      min = "3",
     *      max = "50",
     *      minMessage = "Your address must have at least {{ limit }} characters"),
     *      maxMessage = "Your address cannont have at more than {{ limit }} characters"))
     */
    protected $address;

    /**
     * @ORM\Column(name="address2", type="string", length=255, nullable=true)
     */
    protected $address2;

    /**
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Please enter a city")
     */
    protected $city;

    /**
     * @ORM\ManyToOne(targetEntity="State", fetch="EAGER")
     * @ORM\JoinColumn(name="stateID", referencedColumnName="id", nullable=true)
     */
    protected $state;

    /**
     * @ORM\ManyToOne(targetEntity="Country",  fetch="EAGER")
     * @ORM\JoinColumn(name="countryID", referencedColumnName="id", nullable=false)
     * @Assert\NotBlank(message="Please select a country")
     */
    protected $country;

    /**
     * @ORM\Column(name="zipcode", type="string", length=12, nullable=true)
     */
    protected $zipcode;

    /**
     * @ORM\Column(name="phoneNumber", type="string", length=15, nullable=false)
     * @Assert\Length(
     *      min = "7",
     *      max = "15",
     *      minMessage = "Phone number must be at least {{ limit }} characters long",
     *      maxMessage = "Phone number cannot be longer than than {{ limit }} characters long")
     * @Assert\NotBlank(message="Please enter a phone number")
     */
    protected $phoneNumber;

    /**
     * @ORM\OneToOne(targetEntity="Sonata\UserBundle\Entity\User", mappedBy="address", fetch="LAZY")
     * @ORM\JoinColumn(name="patient_id", referencedColumnName="id", nullable=false, unique=true)
     */
    private $user;

    public function getId() {
        return $this->id;
    }

    /**
     * Set address
     *
     * @param string $address
     */
    public function setAddress($address) {
        $this->address = $address;
        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * Set address2
     *
     * @param string $address2
     */
    public function setAddress2($address2) {
        $this->address2 = $address2;
        return $this;
    }

    /**
     * Get address2
     *
     * @return string
     */
    public function getAddress2() {
        return $this->address2;
    }

    /**
     * Set city
     *
     * @param string $city
     */
    public function setCity($city) {
        $this->city = $city;
        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * Set state
     *
     * @param string $state
     */
    public function setState($state) {
        $this->state = $state;
        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState() {
        return $this->state;
    }

    /**
     * Set country
     *
     * @param string $country
     */
    public function setCountry($country) {
        $this->country = $country;
        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * Set zipcode
     *
     * @param string $zipcode
     */
    public function setZipcode($zipcode) {
        $this->zipcode = $zipcode;
        return $this;
    }

    /**
     * Get zipcode
     *
     * @return string
     */
    public function getZipcode() {
        return $this->zipcode;
    }

    public function getPhoneNumber() {
        return $this->phoneNumber;
    }

    public function setPhoneNumber($phoneNumber) {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getUser() {
        return $this->user;
    }

    public function setUser($user) {
        $this->user = $user;
        return $this;
    }

    public function __toString() {
        $string = $this->address . '<br />';

        if ($this->address2 != "") {
            $string .= $this->address2 . '<br />';
        }

        if ($this->getState() != null) {
            $string .= $this->city . ', ' . $this->getState()->getCode() . ' ' . $this->getCountry()->getCode() . ' ' . $this->zipcode;
        } else {
            $string .= $this->city . ' ' . $this->getCountry()->getCode() . ' ' . $this->zipcode;
        }

        return $string;
    }

    public function isValid() {
        if ($this->address == "" || $this->city == "" || $this->country == "") {
            return false;
        } else {
            return true;
        }
    }

    public function isStateValid(ExecutionContext $context) {
        $propertyPath = $context->getPropertyPath() . '.state';

        if ($this->getCountry() != null) {
            if (($this->getCountry()->getCode() == "US" || $this->getCountry()->getCode() == "CA") && $this->getState() == null) {
                $context->addViolationAt($propertyPath, 'Please select a State', array(), null);
                return false;
            } else {
                return true;
            }
        }
    }

    public function isZipcodeValid(ExecutionContext $context) {
        $propertyPath = $context->getPropertyPath() . '.zipcode';
        
        if ($this->getCountry() != null) {
            if ($this->getCountry()->getZipCodeRequired() && $this->getZipcode() == null) {
                $context->addViolationAt($propertyPath, 'Please enter a Zipcode', array(), null);
                return false;
            } else {
                return true;
            }
        }
    }
}