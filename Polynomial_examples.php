<?php

//header('Content-type: text/plain');

include 'Math/Polynomial.php';
include 'Math/PolynomialOp.php';

echo("<br />-- Algebra --<br />");
$p = new Math_Polynomial('3x^2 + 2x');
$q = new Math_Polynomial('4x + 1');
echo('P is: ' . $p->toString() . "<br />");
echo('Q is: ' . $q->toString() . "<br />");

$mul = Math_PolynomialOp::mul($p, $q); // Multiply p by q
echo('P multiplied by Q is: ' . $mul->toString() . "<br />"); // Print string representation

echo('The degree of that result is: ' . $mul->degree() . "<br />");
echo('That result evaluated at x = 10 is: ' . number_format(Math_PolynomialOp::evaluate($mul, 10)) . "<br />");

$sub = Math_PolynomialOp::sub($p, $q);
echo('P minus Q is: ' . $sub->toString() . "<br />");

$r = new Math_Polynomial('3x^3 - 5x^2 + 10x-3');
$s = new Math_Polynomial('3x+1');
$remainder = new Math_Polynomial();

echo('R is: ' . $r->toString() . "<br />");
echo('S is: ' . $s->toString() . "<br />");

$div = Math_PolynomialOp::div($r, $s, $remainder);
echo('R divided by S is: ' . $div->toString() . ' ( remainder of: ' . $remainder->toString() . ' )' . "<br />");


echo("<br />-- Creating Polynomials --<br />");
$roots = Math_PolynomialOp::createFromRoots(1, 2, -3);
echo('Here is a polynomial with the roots 1, 2, and -3: ' . $roots->toString() . "<br />");


echo("<br />-- Derivatives --<br />");
echo('f(x) is: ' . $p->toString() . "<br />");

$der1 = Math_PolynomialOp::getDerivative($p);
echo('f\'(x) is: ' . $der1->toString() . ' (first derivative)' . "<br />");

$der2 = Math_PolynomialOp::getDerivative($p, 2);
echo('f\'\'(x) is: ' . $der2->toString() . ' (second derivative)' . "<br />");

echo("<br />");

?>
