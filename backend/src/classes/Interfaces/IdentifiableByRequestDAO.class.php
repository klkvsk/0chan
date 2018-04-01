<?php
interface IdentifiableByRequestDAO {
    /** @return IdentifiableObject */
    public function getByRequestedValue($value);
}