<?php


namespace AppBundle\DataFixtures\ORM;


use AppBundle\Entity\User;
use Arkounay\BlockBundle\Entity\PageBlock;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData implements FixtureInterface, ContainerAwareInterface
{

	/**
	 * @var ContainerInterface
	 */
	private $container;

	public function setContainer(ContainerInterface $container = null)
	{
		$this->container = $container;
	}


	/**
	 * Load data fixtures with the passed EntityManager
	 *
	 * @param ObjectManager $manager
	 */
	public function load(ObjectManager $manager)
	{

		$userAdmin = new User();
		$userAdmin->setUsername('superadmin');
		$encoder = $this->container->get('security.password_encoder');
		$password = $encoder->encodePassword($userAdmin, 'superadmin');
		$userAdmin->setPassword($password);
		$userAdmin->setEmail('a.gribet@gmail.com');
		$userAdmin->setEnabled(true);
		$userAdmin->setRoles(['ROLE_SUPER_ADMIN']);
		$manager->persist($userAdmin);
		$manager->flush();

	}
}