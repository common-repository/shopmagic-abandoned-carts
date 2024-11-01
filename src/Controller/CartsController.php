<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Controller;

use WPDesk\ShopMagicCart\Cart\BaseCart;
use WPDesk\ShopMagicCart\Database\CartManager;
use WPDesk\ShopMagic\Components\Database\Abstraction\DAO\ObjectRepository;
use WPDesk\ShopMagic\Components\Database\Abstraction\RequestToCriteria;
use WPDesk\ShopMagicCart\Cart\Cart;
use WPDesk\ShopMagicCart\Normalizer\CartNormalizer;
use WPDesk\ShopMagic\Components\Routing\HttpProblemException;

class CartsController {

	/** @var ObjectRepository<BaseCart> */
	private $repository;

	/** @var CartNormalizer */
	private $normalizer;

	/** @var CartManager */
	private $manager;

	public function __construct( CartManager $manager, CartNormalizer $normalizer ) {
		$this->repository = $manager->get_repository();
		$this->normalizer = $normalizer;
		$this->manager    = $manager;
	}

	/**
	 * @param \WP_REST_Request<array{
	 *    page?: int<1, max>,
	 *    perPage?: int<1, 100>,
	 *    filters?: array<string, mixed>,
	 * }> $request
	 */
	public function index( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response(
			$this->repository
				->find_by( ...$this->parse_params( $request ) )
				->map( \Closure::fromCallable( [ $this->normalizer, 'normalize' ] ) )
				->to_array()
		);
	}

	public function show( int $id ): \WP_REST_Response {
		return new \WP_REST_Response( $this->normalizer->normalize( $this->repository->find( $id ) ) );
	}

	/**
	 * @param \WP_REST_Request<array{
	 *    page?: int<1, max>,
	 *    perPage?: int<1, 100>,
	 *    filters?: array<string, mixed>,
	 * }> $request
	 */
	public function count( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( $this->repository->get_count( ...$this->parse_params( $request ) ) );
	}

	public function delete( int $id ): \WP_REST_Response {
		try {
			$cart = $this->manager->find( $id );
		} catch ( \Exception $e ) {
			throw new HttpProblemException(
				[
					'title' => esc_html__( 'Cart not found', 'shopmagic-abandoned-carts' ),
				],
				\WP_Http::NOT_FOUND,
				$e
			);
		}

		$success = $this->manager->delete( $cart );

		if ( $success === 0 ) {
			throw new HttpProblemException(
				[
					'title' => esc_html__( 'Failed to delete cart', 'shopmagic-abandoned-carts' ),
				],
				\WP_Http::UNPROCESSABLE_ENTITY
			);
		}

		return new \WP_REST_Response( null, \WP_Http::NO_CONTENT );
	}

	/**
	 * @param \WP_REST_Request<array{
	 *    page?: int<1, max>,
	 *    perPage?: int<1, 100>,
	 *    filters?: array<string, mixed>,
	 * }> $request
	 *
	 * @return array{
	 *  0: array<string, mixed>,
	 *  1: array<string, mixed>,
	 *  2: int,
	 *  3: int,
	 * }
	 */
	public function parse_params( \WP_REST_Request $request ): array {
		$criteria = ( new RequestToCriteria() )
			->set_where_whitelist(
				[
					'status' => [
						Cart::ACTIVE,
						Cart::ABANDONED,
						Cart::SUBMITTED,
						Cart::ORDERED,
						Cart::RECOVERED,
					],
				]
			)
			->set_order_keys( [ 'created', 'last_modified' ] );

		[ $where, $order, $offset, $limit ] = $criteria->parse_request( $request );

		if ( empty( $order ) ) {
			$order['last_modified'] = 'DESC';
		}

		if ( ! isset( $where['status'] ) ) {
			$where['remove_unknown'] = [
				'field'     => 'status',
				'condition' => 'NOT LIKE',
				'value'     => Cart::FRESH,
			];
		}

		return [ $where, $order, $offset, $limit ];
	}
}
