<?php
class Jp7_PageMap {
	public function getHtml() {
		?>
		<PageMap>
			<?php foreach ($this as $type => $attributes) { ?>
				<DataObject type="<?php echo $type; ?>">
					<?php foreach ($attributes as $name => $values) { ?>
						<?php foreach ($values as $value) { ?>
							<Attribute name="<?php echo $name; ?>" value="<?php echo $value; ?>" />
						<?php } ?>
					<?php } ?>
				</DataObject>
			<?php } ?>
		</PageMap>
		<?php
	}
}