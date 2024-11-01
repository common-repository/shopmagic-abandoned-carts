<?php
/**
 * @var string $title
 * @var string $content
 * @var string $button_text
 * @var string $email_placeholder
 * @var string $action
 * @var string $nonce
 */

?>
<style>
	dialog.shopmagic-exit-intent {
		--sm-dialog-background: #c9c9c9;
		--sm-dialog-text-color: #111111;
		--sm-dialog-radius: 0.25rem;
		--sm-dialog-shadow: 3px 4px 6px 2px rgba(0, 0, 0, 0.12);
		--sm-dialog-border: none;

		max-width: 90vw;
		width: 75ch;
		padding: 2rem 2.5rem;
		background: var(--sm-dialog-background);
		color: var(--sm-dialog-text-color);
		border-radius: var(--sm-dialog-radius);
		border: var(--sm-dialog-border);
		box-shadow: var(--sm-dialog-shadow);
	}

	@media (min-width: 680px) {
		dialog.shopmagic-exit-intent {
			padding: 5rem 3.5rem;
		}
	}

	dialog.shopmagic-exit-intent > * + * {
		margin-block-start: 1.5rem;
		margin-block-end: 0;
	}

	.shopmagic-exit-intent__close {
		position: absolute;
		top: 1rem;
		right: 1rem;
		font-size: 1.25rem;
		line-height: 1;
		padding: .25rem;
	}

	.shopmagic-exit-intent__title {
		font-size: 2.5rem;
		line-height: 1.2;
		font-weight: 300;
	}

	.shopmagic-exit-intent__form {
		display: flex;
		flex-wrap: wrap;
	}

	.shopmagic-exit-intent__input {
		width: 45ch;
		border: 1px solid black;
		border-radius: var(--sm-dialog-radius);
		padding: 1ex;
	}

	.shopmagic-exit-intent__close, .shopmagic-exit-intent__submit {
		background: transparent;
		border: none;
		border-radius: var(--sm-dialog-radius);
	}
	.shopmagic-exit-intent__submit {
		background: var(--sm-dialog-text-color);
		color: var(--sm-dialog-background);
		flex: 1 1 auto;
	}

	.sr-only {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		border: 0;
	}
</style>

<dialog class="shopmagic-exit-intent">
	<button class="shopmagic-exit-intent__close" type="reset" form="exit-intent">&#10799</button>
	<p class="shopmagic-exit-intent__title"><?php echo wp_kses_post( $title ); ?></p>
	<p class="shopmagic-exit-intent__content"><?php echo wp_kses_post( $content ); ?></p>
	<form class="shopmagic-exit-intent__form" id="exit-intent" method="dialog">
		<input name="action" type="hidden" value="<?php echo esc_attr( $action ); ?>">
		<input name="nonce" type="hidden" value="<?php echo esc_attr( $nonce ); ?>">
		<label class="sr-only" for="sm-exit-email"><?php esc_html_e( 'Enter your email', 'shopmagic-abandoned-carts' ); ?></label>
		<input name="email"
			   autocomplete="email"
			   autofocus
			   class="shopmagic-exit-intent__input"
			   id="sm-exit-email"
			   type="email"
			   placeholder="<?php esc_html_e( 'Enter your email', 'shopmagic-abandoned-carts' ); ?>">
		<button class="shopmagic-exit-intent__submit"><?php esc_html_e( 'Save', 'shopmagic-abandoned-carts' ); ?></button>
	</form>
</dialog>
