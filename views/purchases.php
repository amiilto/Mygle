<h1>Compras</h1>

<div class="button"><a href="<?php echo BASE_URL; ?>/purchases/add">Adicionar Compra</a></div>

<table border="0" width="100%">
	<tr>
		<th>Nome do Fornecedor</th>
		<th>Data</th>
		<th>Status</th>
		<th>Valor</th>
		<th>Ações</th>
	</tr>
	<?php foreach($purchases_list as $purchase_item): ?>
	<tr>
		<td><?php echo $purchase_item['name']; ?></td>
		<td><?php echo date('d/m/Y', strtotime($purchase_item['date_purchase'])); ?></td>
		<td><?php echo $statuses[$purchase_item['status']]; ?></td>
		<td>R$ <?php echo number_format($purchase_item['total_price'], 2, ',', '.'); ?></td>
		<td>
			<div class="button button_small"><a href="<?php echo BASE_URL; ?>/purchase/edit/<?php echo $purchase_item['id']; ?>">Editar</a></div>
			<?php if(!empty($purchase_item['nfe_key'])): ?>
				<div class="button button_small"><a target="_blank" href="<?php echo BASE_URL; ?>/purchase/view_nfe/<?php echo $purchase_item['nfe_key']; ?>">Visualizar NF-e</a></div>
			<?php else: ?>
				<div class="button button_small"><a target="_blank" href="<?php echo BASE_URL; ?>/purchase/generate_nfe/<?php echo $purchase_item['id']; ?>">Emitir NF-e</a></div>
			<?php endif; ?>
		</td>
	</tr>
	<?php endforeach; ?>
</table>