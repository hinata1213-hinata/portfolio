<?php
class SimpleCalculator
{
    private int $total;


    public function __construct(int $initialValue)
    {
        $this->total = $initialValue;
    }

    public function add(int $value): int
    {
        $this->total += $value;
        return $this->total;
    }
} 

$calculator = new SimpleCalculator(10);
echo $calculator->add(5);